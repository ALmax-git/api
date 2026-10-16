<?php

namespace App\Enums;

enum AssetsType: string
{
    case CURRENCY   = 'currency';
    case FIAT       = 'fiat';
    case CRYPTO     = 'crypto';
    case STOCK      = 'stock';
    case NFTS       = 'NFTs';
    case NATIVE     = 'native';
    case ERC20      = 'erc20';
    case ERC721     = 'erc721';
    case BEP20      = 'bep20';
    case SPL        = 'spl';
    case ERC1155    = 'erc1155';


    public function label(): string
    {
        return match ($this) {
            self::CURRENCY => _app('currency'),
            self::FIAT     => _app('fiat'),
            self::CRYPTO   => _app('crypto'),
            self::STOCK    => _app('stock'),
            self::NFTS     => _app('nfts'),
            self::NATIVE   => _app('native'),
            self::ERC20    => _app('erc20'),
            self::ERC721   => _app('erc721'),
            self::BEP20    => _app('bep20'),
            self::SPL      => _app('spl'),
            self::ERC1155  => _app('erc1155'),
        };
    }

    public static function values(): array
    {
        return array_map(
            fn($type) => $type->value,
            self::cases()
        );
    }

    public static function options(): array
    {
        return array_map(
            fn($type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
