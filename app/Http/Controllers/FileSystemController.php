<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContractFile;
use App\Models\FileSystemItem;
use App\Models\Contract;
use App\Models\ContractSigner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Spatie\Image\Image;

class FileSystemController extends Controller
{
    // app/Http/Controllers/Api/FileSystemController.php


    public function new_signature(Request $request)
    {
        $request->validate([
            'blob' => 'required|image|max:6024',
        ]);

        $user = User::find(get_id($request->uuid, 'user'));
        $client = $user->client;
        $signatureFile = $request->file('blob');

        // 🔹 Resolve folder once
        $folder_id = FileSystemItem::where('type', 'folder')
            ->where('owner_id', $client->owner->id)
            ->where('name', 'Business')
            ->value('id');

        Log::info(
            'New Signature Request',
            [get_id($request->uuid, 'user'), $request->uuid, $request->all()]
        );
        if (!$folder_id) {
            return response()->json(['message' => 'Business folder not found for client owner ID: ' . $client->owner->id], 422);
        }

        // 🔹 Resolve base URL once
        $host_data = _get_host();
        $base = (!empty($host_data['domain']))
            ? (str_starts_with($host_data['domain'], 'http')
                ? $host_data['domain']
                : 'https://' . $host_data['domain'])
            : url('/');

        try {
            DB::transaction(function () use ($user, $client, $folder_id, $base, $signatureFile) {
                // 🔒 Lock client row for safe quota update
                $lockedClient = Client::where('id', $client->id)->lockForUpdate()->first();

                $mime = $signatureFile->getMimeType();
                $originalName = $signatureFile->getClientOriginalName();
                $originalSize = $signatureFile->getSize();

                // Generate file name & folder
                $filename = Str::random(16);
                $extension = str_starts_with($mime, 'image/') ? 'jpg' : $signatureFile->getClientOriginalExtension();
                $filename .= '.' . $extension;
                $relativePath = 'files/' . $user->id . '/' . $filename;
                $fullStoragePath = storage_path('app/public/' . $relativePath);

                // Ensure folder exists
                if (!file_exists(dirname($fullStoragePath))) {
                    mkdir(dirname($fullStoragePath), 0755, true);
                }

                // 🖼️ Compress image if applicable and large enough
                if (str_starts_with($mime, 'image/') && $originalSize > 300 * 1024) {
                    $tempPath = $signatureFile->getRealPath();
                    Image::load($tempPath)
                        ->quality(75)
                        ->optimize()
                        ->format('jpg')
                        ->save($fullStoragePath);
                    $finalSize = filesize($fullStoragePath);
                } else {
                    // fallback: just store directly using standard storage disk
                    $signatureFile->storeAs('files/' . $user->id, $filename, 'public');
                    $finalSize = Storage::disk('public')->size($relativePath);
                }

                // ❗ REAL quota enforcement
                if ($lockedClient->used_disk_size + $finalSize > $lockedClient->disk_size) {
                    Storage::disk('public')->delete($relativePath); // rollback stored file
                    throw new \Exception("Storage limit exceeded. File '{$originalName}' was not uploaded.");
                }

                // 🌐 Build URL
                $full_path = rtrim($base, '/') . '/storage/' . ltrim($relativePath, '/');

                // 💾 Save or Update file record
                // If you want to replace an existing signature instead of creating a new record every time:
                $fileSystemItem = FileSystemItem::updateOrCreate(
                    [
                        'owner_id' => $user->id,
                        'parent_id' => $folder_id,
                        'type' => 'file',
                        // Add a unique identifier or custom logic if you track signature files explicitly, 
                        // otherwise use updateOrCreate based on your business rule.
                    ],
                    [
                        'name' => $this->getUniqueFileName($originalName, $folder_id),
                        'path' => $folder_id,
                        'mime_type' => $mime,
                        'size' => $finalSize,
                        'disk' => 'public',
                        'storage_path' => $full_path,
                        'hash' => hash_file('md5', $signatureFile->getRealPath()),
                    ]
                );

                // If the user already had a signature item, you may want to clean up the old file/record here.
                $user->signature_id = $fileSystemItem->id;
                $user->save();

                $lockedClient->increment('used_disk_size', $finalSize);
            });

            return response()->json(['success' => true, 'message' => 'Signature saved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }


    private function getUniqueFileName(string $originalName, mixed $folder_id): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = $originalName;
        $counter = 1;

        while (FileSystemItem::where('parent_id', $folder_id)
            ->where('owner_id', auth()->id())
            ->where('type', 'file')
            ->where('name', $name)
            ->exists()
        ) {
            $name = $baseName . " ($counter)" . ($extension ? ".$extension" : '');
            $counter++;
        }

        return $name;
    }

    /**
     * Handle uploading files for a contract.
     *
     * This endpoint accepts a 'uuid' referencing a contract or file and
     * an array of uploaded files under 'files'. It stores each file in
     * the contracts/{contract_id} directory on the public disk and
     * creates corresponding FileSystemItem and ContractFile records.
     *
     * @param Request $request
     */
    public function contract_files(Request $request, $uuid = null, $files = [])
    {
        // Log initial payload state
        // Log::error('contract validated', [$request, $uuid, $files]);

        // 1. Get UUID from request body first, then fallback to route parameter
        $uuid = $request->input('uuid', $uuid);

        if (!$uuid) {
            return response()->json(['message' => 'UUID parameter is required.'], 422);
        }

        try {
            $contract = Contract::find(get_id($uuid, 'contract'));
            // Log::error('contract atempt 1', [$contract]);
        } catch (\Throwable $th) {
            Log::error('contract atempt 1', [$th]);
            return response('<div class="text-red-500 text-sm">Invalid reference.</div>', 404);
        }

        if (!$contract) {
            $file = FileSystemItem::find(get_id($uuid, 'file_system_item'));
            Log::error('file 2', [$file]);
            if ($file && $file->type == 'file' && $file->mime_type == 'bin/corex/xf') {
                if (is_array($file->metadata) && array_key_exists('contract_id', $file->metadata)) {
                    $contract = Contract::find(get_id($file->metadata['contract_id'], 'contract'));
                }
            }
        }

        if (!$contract) {
            return response()->json(['message' => 'Contract not found.'], 444);
        }

        $uploaded = [];
        Log::error('contract', [$contract, $request]);

        // 2. Safely retrieve uploaded files
        $uploadedFiles = $request->file('files', []);

        if (empty($uploadedFiles)) {
            return response()->json(['message' => 'No files were uploaded or parsed.'], 422);
        }

        foreach ($uploadedFiles as $file) {
            $originalName = $file->getClientOriginalName() ?: 'contract-file';
            $filename = Str::uuid()->toString();
            $extension = $file->getClientOriginalExtension();
            $storedName = $filename . ($extension ? '.' . $extension : '');
            $directory = 'contracts/' . $contract->id;
            $storedPath = $file->storeAs($directory, $storedName, 'public');

            $item = new FileSystemItem();
            $item->owner_id = auth()->id();
            $item->parent_id = null;
            $item->name = $originalName;
            $item->type = 'file';
            $item->path = 'contracts';
            $item->mime_type = $file->getClientMimeType() ?: 'application/octet-stream';
            $item->size = $file->getSize();
            $item->storage_path =  url('/') . '/storage/' .  $storedPath;
            $item->disk = 'public';
            $item->hash = hash_file('sha256', $file->getRealPath() ?: $file->path());
            $item->save();

            ContractFile::create([
                'contract_id' => $contract->id,
                'file_system_item_id' => $item->id,
                'user_id' => auth()->id(),
            ]);

            $uploaded[] = [
                'id' => make_uuid($item->id, 'file_system_item'),
                'name' => $item->name,
                'path' => Storage::disk('public')->url($storedPath),
                'mime_type' => $item->mime_type,
                'size' => $item->size,
            ];
        }

        // if not empty uploaded update contract status to sent
        if (!empty($uploaded)) {
            $contract->status = Contract::STATUS_COMPLETED;
            $contract->save();
            // add log
            $logs = $contract->events_log ?? [];

            $logs[] = [
                'event' => 'files_uploaded',
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString(),
                'details' => [
                    'uploaded_files_count' => count($uploaded),
                    'uploaded_files' => $uploaded,
                ],
            ];
            $contract->events_log = $logs;
        }
        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully.',
            'data' => $uploaded,
        ], 201);
    }
    public function new_sign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255',],
            'file_uuid' => ['required', 'string', 'max:255',],

            'description' => [
                'nullable',
                'string',
            ],

            'requires_verification' => [
                'boolean',
            ],

            'conditions' => [
                'nullable',
                'array',
            ],

            /*
             * At least one signer.
             */
            'signers' => [
                'required',
                'array',
                'min:1',
            ],

            'signers.*.user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'signers.*.role' => [
                'required',
                'string',
                'in:signer,witness,approver',
            ],

            'follow_order' => [
                'boolean',
            ],

        ]);




        $parent = FileSystemItem::where('id', get_id($request->file_uuid, 'file_system_item'))
            ->where('owner_id', auth()->id())
            ->first();

        if (!$parent) {
            return response()->json(['error' => 'Parent folder not found or access denied.'], 404);
        }

        if ($parent->type !== 'folder') {
            return response()->json(['error' => 'Parent item must be a folder.'], 400);
        }

        /*
         * Make sure every signer actually identifies
         * either a User or Client.
         */
        foreach ($validated['signers'] as $signer) {

            if (
                empty($signer['user_id'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Every signer must have either a user_id.',
                ], 422);
            }
        }


        $contract = DB::transaction(function () use (
            $validated,
            $request
        ) {

            $contract = Contract::create([

                'client_id' =>
                $validated['client_id'] ?? ($validated['requires_verification'] ? auth()?->user()->client->id : null),

                'user_id' =>
                $request->user()?->id,

                'title' =>
                $validated['title'],

                'description' =>
                $validated['description'] ? parser($validated['description']) : null,

                'requires_verification' =>
                $validated['requires_verification'] ?? false,

                'verified' =>
                false,

                'conditions' =>
                $validated['conditions'] ?? null,

                'events_log' =>
                [],

                'status' =>
                Contract::STATUS_DRAFT,

            ]);


            /*
             * Create initial signer(s).
             */
            foreach ($validated['signers'] as $index => $signer) {

                $contract->signers()->create([

                    'user_id' =>
                    $signer['user_id'] ?? null,


                    'role' =>
                    $signer['role'],

                    'signing_order' => $index + 1,

                    'follow_order' =>
                    $signer['follow_order'] ?? false,

                    'status' =>
                    ContractSigner::STATUS_PENDING,

                    'is_signed' =>
                    false,

                ]);
            }



            return $contract;
        });


        $contract->load([
            'client',
            'user',
            'signers.user',
            'signers.client',
        ]);

        $item = new FileSystemItem();
        $item->owner_id                       = auth()->id();
        $item->parent_id                      = $parent->id;
        $item->name                           = $validated['title'] . '.xf';
        $item->type                           = 'file';
        $item->path                           = 'contracts';
        $item->metadata                       = ['contract_id' => make_uuid($contract->id, 'contract'),];
        $item->mime_type                      = 'bin/corex/xf';
        $item->size                           = '1';
        $item->storage_path                   = url('/') . '/contracts/' . make_uuid($contract->id, 'contract');
        $item->hash                           = $contract->hash;
        $item->save();
        // make sure uuid is added to each contract
        if (!$contract->uuid) {
            $contract->uuid = make_uuid($contract->id, 'contract');
            // $contract->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Contract created successfully.',
            'data' => $contract,
        ], 201);
    }

    public function contracts(): JsonResponse
    {
        $contracts = Contract::query()
            ->where(function ($query) {
                $query->where('client_id', auth()->user()->client_id)
                    ->orWhere('user_id', auth()->id());
            })
            ->with([
                'client',
                'user',
                'signers.user',
                'signers.client',
                'files.file',
            ])
            ->latest()
            ->get();

        // make sure uuid is added to each contract
        $contracts->each(function ($contract) {
            if (!$contract->uuid) {
                $contract->uuid = make_uuid($contract->id, 'contract');
                // $contract->save();
            }
        });

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }
    public function getDrives(): JsonResponse
    {
        $user           = auth()->user();

        // 1. Fetch all root items (where parent_id is null)
        $rootItems      = FileSystemItem::where('owner_id', $user->id)->whereNull('parent_id')->get();

        // 2. Separate "Business" from the rest of the Home (Home) items
        if ($user->is_admin()) {
            if (!FileSystemItem::where('owner_id', $user->client->owner->id)->where('name', 'Business')->first()) {
                FileSystemItem::create([
                    'name' => 'Business',
                    'type' => 'folder',
                    'owner_id' => $user->client->owner->id,
                ]);
            }
        }

        // 2. Separate "Business" from the rest of the Home (Home) items
        $businessRoot   = FileSystemItem::where('owner_id', $user->client->owner->id)->where('name', 'Business')->get();
        $homeItems      = $rootItems->where('name', '!=', 'Business');

        $response       = [
            'explorer' => [
                'path' => [
                    'folder' => [
                        'User' => [
                            'folder'            => $this->buildTree($homeItems->where('type', 'folder')),
                            'file'              => $this->formatFiles($homeItems->where('type', 'file')),
                            'used_disk_size'    => Number::fileSize($user->used_disk_size, precision: 2),
                            'disk_size'         => Number::fileSize($user->disk_size  ?? (5 * 1024 * 1024 * 1024), precision: 2)
                        ]
                    ]
                ]
            ]
        ];

        // 3. Add Business drive only if user is admin and it exists
        if ($user->is_admin() && $businessRoot) {
            $response['explorer']['path']['folder']['Business'] = [
                'folder'            => $this->buildTree($businessRoot?->where('type', 'folder')),
                'file'              => $this->formatFiles($businessRoot?->where('type', 'file')),
                'used_disk_size'    => Number::fileSize($user->client->used_disk_size, precision: 2),
                'disk_size'         => Number::fileSize($user->client->disk_size  ?? (5 * 1024 * 1024 * 1024), precision: 2)
            ];
        } else {
            $response['explorer']['path']['folder']['Business'] = [
                'folder'            => $this->buildTree($homeItems->where('type', 'folder')),
                'file'              => $this->formatFiles($homeItems->where('type', 'file')),
                'used_disk_size'    => Number::fileSize($user->used_disk_size, precision: 2),
                'disk_size'         => Number::fileSize($user->disk_size ?? (5 * 1024 * 1024 * 1024), precision: 2)
            ];
        }

        return response()->json($response);
    }
    private function buildTree($items)
    {
        $tree = [];

        foreach ($items as $item) {
            $tree[$item->name] = [
                'id' => make_uuid($item->id, 'file_system_item'),
                'name' => $item->name,
                'folder' => $this->buildTree(
                    $item->children->where('type', 'folder')
                ),
                'file' => $this->formatFiles(
                    $item->children->where('type', 'file')
                )
            ];
        }

        return $tree;
    }

    private function formatFiles($items)
    {
        return $items->where('type', 'file')->map(function ($file) {
            return [
                'id' => make_uuid($file->id, 'file_system_item'),
                'name' => $file->name,
                'ico' => $file->thumbnail_path ?? $this->getIcon($file),
                'command' => $file->metadata['command'] ?? "apps.explorer.open_file('" . $this->getFullPath($file) . "');",
                'path' => $this->getFullPath($file),
                'mime_type' => $file->mime_type,
                'content' => $this->getContent($file),
            ];
        })->values()->all();
    }

    private function getContent(FileSystemItem $file)
    {
        if ($file->type !== 'file' || !$file->storage_path) {
            return null;
        }

        // text file only 
        if (Str::startsWith($file->mime_type, 'text/')) {
            try {
                // open the file from storage and return its content
                return Storage::disk($file->disk)->get($file->storage_path);
            } catch (\Exception $e) {
                Log::error("Failed to read file content: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }

    private function getFullPath(FileSystemItem $file)
    {
        $path = '';
        //1 check if it type = file
        if ($file->type === 'file') {
            // if its storage path exist return it
            if ($file->storage_path) {
                // if path start with http
                if (Str::startsWith($file->storage_path, ['http://', 'https://'])) {
                    return $file->storage_path;
                }
                // if the storage path is in public disk return the public url
                if (Storage::disk($file->disk)->exists($file->storage_path)) {
                    $path = Storage::disk($file->disk)->url($file->storage_path);
                    // make full https 
                    if (Str::startsWith($path, 'http://')) {
                        $path = Str::replaceFirst('http://', 'https://', $path);
                    }
                    return $path;
                }
                if (!$path) {
                    // try check public disk with storage . path
                    if (Storage::disk('public')->exists('storage/' . $file->storage_path)) {
                        $path = Storage::disk('public')->url($file->storage_path);
                    }
                }
            }
        }
        // if path is empty return the name
        return $path ?: $file->name;
    }


    private function getIcon($file)
    {
        // Quick mapping for your UI
        //exe
        if (str_ends_with($file->name, '.exe')) return "icon/terminal.svg";
        if (str_contains($file->mime_type, 'word')) return "icon/files/word.png";
        // image 
        if (str_starts_with($file->mime_type, 'image/')) return $file->thumbnail_path ?? "icon/files/picture.png";
        //video
        if (str_starts_with($file->mime_type, 'video/')) return "icon/files/video.png";
        // text
        if (str_starts_with($file->mime_type, 'text/')) return "icon/files/txt.png";
        // pdf
        if (str_contains($file->mime_type, 'pdf')) return "icon/files/filetype-pdf.svg";
        //pptx 
        if (str_contains($file->mime_type, 'presentation')) return "icon/files/ppt.png";
        //excell 
        if (str_contains($file->mime_type, 'spreadsheet')) return "icon/files/excel.png";
        if (str_contains($file->mime_type, 'bin/corex/xf')) return "icon/files/xf.svg";
        // default
        return "icon/files/none.png";
    }
    public function store(Request $request): JsonResponse
    {
        Log::info('Rename Item Request', $request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:file,folder,files',
            'parent_id' => 'nullable|string'
        ]);
        $path = null;
        $file = null;


        if ($request->parent_id) {
            $parent = FileSystemItem::where('id', get_id($request->parent_id, 'file_system_item'))
                ->where('owner_id', auth()->id())
                ->first();

            if (!$parent) {
                return response()->json(['error' => 'Parent folder not found or access denied.'], 404);
            }

            if ($parent->type !== 'folder') {
                return response()->json(['error' => 'Parent item must be a folder.'], 400);
            }
        }
        // if type filecreate the new empty text file on public disk

        if ($request->type === 'file') {

            $filename = Str::slug(pathinfo($request->name, PATHINFO_FILENAME));
            $extension = pathinfo($request->name, PATHINFO_EXTENSION);

            $unique = Str::uuid();

            $path = "user_" . auth()->id() . "/{$unique}_{$filename}" . ($extension ? ".{$extension}" : "");

            $file = Storage::disk('public')->put($path, '');
        }
        if (FileSystemItem::where('type', $request->type)->where('parent_id', $request->parent_id ? get_id($request->parent_id, 'file_system_item') : null)->where('owner_id', auth()->id())->where('name', $request->name)->exists()) {
            return response()->json(['error' => 'An item with the same name already exists in this folder.'], 400);
        }
        if ($request->type === 'folder' && FileSystemItem::where('type', 'folder')->where('parent_id',  $request->parent_id ? get_id($request->parent_id, 'file_system_item') : null)->where('owner_id', auth()->id())->where('name', $request->name)->exists()) {
            return response()->json(['error' => 'A folder with the same name already exists in this folder.'], 400);
        }
        if ($request->type === 'folder') {

            $folder = FileSystemItem::find(get_id($request->parent_id, 'file_system_item'));
            $path = $folder ? $folder->getFullPath() : '/';
        }
        $item = new FileSystemItem();
        $item->owner_id                       = auth()->id();
        $item->parent_id                      = $request->parent_id ? get_id($request->parent_id, 'file_system_item') : null;
        $item->name                           = $request->name;
        $item->type                           = $request->type;
        $item->path                           = $path;
        $item->type                           = $request->type == 'folder'           ? 'folder'                                                  : 'file';
        if ($request->type === 'file') {
            $item->mime_type                      = $request->type === 'file'            ? mime_content_type(Storage::disk('public')->path($path))   : '';
            $item->size                           = $request->type === 'file'            ? Storage::disk('public')->size($path)                      : '';
            $item->storage_path                   = Storage::disk('public')->path($path);
            $item->hash                           = $request->type === 'file'            ? hash_file('sha256', Storage::disk('public')->path($path)) : '';
        }
        $item->save();

        return response()->json([
            'item' => [
                'id' => make_uuid($item->id, 'file_system_item'),
                'name' => $item->name,
                'type' => $item->type,
                'ico' => $this->getIcon($item),
                'command' => "apps.explorer.open_file('" . ($item->id) . "');"
            ]
        ]);
    }
    public function rename(Request $request)
    {
        Log::info('Rename Item Request', $request->all());
        $request->validate([
            'id' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $id = get_id($request->id, 'file_system_item');

        $item = FileSystemItem::where('owner_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        $oldPath = $item->path;
        $newPath = null;

        // if ($item->type === 'file' && $oldPath) {

        //     $dir = dirname($oldPath);  
        //     $ext = pathinfo($oldPath, PATHINFO_EXTENSION);

        //     $filename = Str::slug(pathinfo($request->name, PATHINFO_FILENAME));

        //     $newPath = $dir . '/' . $filename . ($ext ? '.' . $ext : '');

        //     Storage::disk('public')->move($oldPath, $newPath);

        //     $item->path = $newPath;
        // }

        $item->name = $request->name;
        $item->save();

        return response()->json([
            'success' => true,
            'name' => $item->name
        ]);
    }
}
