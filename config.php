<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BLOCKSAFE ETHEREUM CONFIGURATION
|--------------------------------------------------------------------------
*/

define('APP_NAME', 'BlockSafe Ethereum');

define('BASE_URL', '/blocksafe_ethereum/public');

// DATABASE CONFIGURATION
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'blokchain');
define('DB_USER', 'root');
define('DB_PASS', '');

// BLOCKCHAIN CONFIGURATION
define('ETH_CHAIN_ID', 11155111);

define(
    'ETH_RPC_URL',
    'https://sepolia.infura.io/v3/GANTI_DENGAN_PROJECT_ID'
);

// SMART CONTRACT
define(
    'ETH_CONTRACT_ADDRESS',
    '0xGANTI_DENGAN_ALAMAT_KONTRAK'
);

// FILE UPLOAD
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024);

define(
    'UPLOAD_DIR',
    dirname(__DIR__) . '/storage/documents/'
);

// TIMEZONE
date_default_timezone_set('Asia/Jakarta');