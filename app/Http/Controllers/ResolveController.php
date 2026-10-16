<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Blog;
use App\Models\Contract;
use App\Models\FileShare;
use App\Models\FileSystemItem;
use App\Models\JournalEntry;
use App\Models\PosCart;
use App\Models\ProductVariant;
use App\Models\Project;
use App\Models\Stock;
use App\Models\StockEntry;
use App\Models\Todo;

class ResolveController extends Controller
{
    public function contract(Request $request, string $contract): Mixed
    {
        $uuid = $contract;
        try {
            $contract = Contract::find(get_id($uuid, 'contract'));
        } catch (\Throwable $th) {
            return response('<div class="text-red-500 text-sm">Invalid reference.</div>', 404);
        }
        if (!$contract) {
            $file = FileSystemItem::find(get_id($uuid, 'file_system_item'));
            if ($file)
                if ($file->type == 'file' && $file->mime_type == 'bin/corex/xf') {
                    // dd($file, is_array($file->metadata), $file->metadata);
                    if (is_array($file->metadata))
                        if (array_key_exists('contract_id', $file->metadata))
                            $contract = Contract::find(get_id($file->metadata['contract_id'], 'contract'));
                }
        }

        if (!$contract) {
            return response('<div class="text-red-500 text-sm">Invalid reference.</div>', 404);
        }
        return view('api.resolve.contract', compact('contract'));
    }
    public function show(string $model, string $uuid): Mixed
    {
        $id = get_id($uuid, $model);

        if (!$id) {
            return response('<div class="text-red-500 text-sm">Invalid reference.</div>', 404);
        }

        return match ($model) {

            'user' => $this->user($id),

            'client' => $this->client($id),

            'product' => $this->product($id),

            'product_variant' => $this->product_variant($id),

            'blog' => $this->blog($id),

            'journal_entry' => $this->journal_entry($id),

            'stock' => $this->stock($id),

            'stock_entry' => $this->stock_entry($id),

            'file_share' => $this->file_share($id),

            'project' => $this->project($id),

            'task' => $this->task($id),

            'pos_cart' => $this->pos_cart($id),

            default => response(
                '<div class="text-red-500 text-sm">Unsupported reference.</div>',
                404
            )
        };
    }

    private function journal_entry(string $id): Mixed
    {
        $entry = JournalEntry::find($id);

        if (!$entry) {
            return response('<div class="text-red-500 text-sm">Journal not found.</div>', 404);
        }
        $mode = "public";
        if (auth()->user()) {
            if (!auth()->user()->is_admin() && auth()->user()->client->id === $entry->journal->client->id) $mode = 'client';
            if (auth()->user()->is_admin() && auth()->user()->client->id === $entry->journal->client->id) $mode = 'full';
        }
        return response(view('api.resolve.journal_entry', compact('entry', 'mode'))->render());
    }

    private function user(string $id): Mixed
    {
        $user = User::find($id);

        if (!$user) {
            return response('<div class="text-red-500 text-sm">User not found or User Profile not public.</div>', 404);
        }

        return response(view('api.resolve.user', compact('user'))->render());
    }

    private function client(string $id): Mixed
    {
        $client = Client::find($id);

        if (!$client) {
            return response('<div class="text-red-500 text-sm">Client not found.</div>', 404);
        }

        return response(view('api.resolve.client', compact('client'))->render());
    }
    private function pos_cart(string $id): Mixed
    {
        $cart = PosCart::find($id);

        if (!$cart) {
            return response('<div class="text-red-500 text-sm">Client not found.</div>', 404);
        }

        return response(view('api.resolve.pos_cart', compact('cart'))->render());
    }

    private function product(string $id): Mixed
    {
        $product = Product::find($id);

        if (!$product) {
            return response('<div class="text-red-500 text-sm">Product not found.</div>', 404);
        }

        return response(view('api.resolve.product', compact('product'))->render());
    }

    private function product_variant(string $id): Mixed
    {
        $product_variant = ProductVariant::find($id);

        if (!$product_variant) {
            return response('<div class="text-red-500 text-sm">Product Variant not found.</div>', 404);
        }

        return response(view('api.resolve.product_variant', compact('product_variant'))->render());
    }

    private function blog(string $id): Mixed
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response('<div class="text-red-500 text-sm">Blog not found.</div>', 404);
        }

        return response(view('api.resolve.blog', compact('blog'))->render());
    }

    private function stock(string $id): Mixed
    {
        $stock = Stock::find($id);

        if (!$stock) {
            return response('<div class="text-red-500 text-sm">Stock not found.</div>', 404);
        }

        return response(view('api.resolve.stock', compact('stock'))->render());
    }

    private function stock_entry(string $id): Mixed
    {
        $stockEntry = StockEntry::find($id);

        if (!$stockEntry) {
            return response('<div class="text-red-500 text-sm">Stock entry not found.</div>', 404);
        }

        return response(view('api.resolve.stock_entry', compact('stockEntry'))->render());
    }

    private function file_share(string $id): Mixed
    {
        $fileShare = FileShare::find($id);

        if (!$fileShare) {
            return response('<div class="text-red-500 text-sm">File share not found.</div>', 404);
        }

        return response(view('api.resolve.file_share', compact('fileShare'))->render());
    }

    private function project(string $id): Mixed
    {
        $project = Project::find($id);
        if (!$project) {
            return response('<div class="text-red-500 text-sm">Project not found.</div>', 404);
        }
        $mode = "public";
        if (auth()->user()) {
            if (!auth()->user()->is_admin() && auth()->user()->client->id === $project->client?->id) $mode = 'client';
            if (auth()->user()->is_admin() && auth()->user()->client->id === $project->client?->id) $mode = 'full';
        }
        return response(view('api.resolve.project', compact('project', 'mode'))->render());
    }

    private function task(string $id): Mixed
    {
        $task = Todo::find($id);
        if (!$task) {
            return response('<div class="text-red-500 text-sm">Task not found.</div>', 404);
        }
        return response(view('api.resolve.task', compact('task'))->render());
    }
}
