<?php

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/handler/Response.php';
require_once __DIR__ . '/handler/AssetHandler.php';
require_once __DIR__ . '/handler/HealthHandler.php';
require_once __DIR__ . '/handler/ScanHandler.php';

require_once __DIR__ . '/router/Router.php';

require_once __DIR__ . '/scanner/SubdomainScanner.php';
require_once __DIR__ . '/scanner/WHOISScanner.php';
require_once __DIR__ . '/scanner/DNSScanner.php';
require_once __DIR__ . '/scanner/IPScanner.php';
require_once __DIR__ . '/scanner/PortScanner.php';
require_once __DIR__ . '/scanner/SSLScanner.php';
require_once __DIR__ . '/scanner/TechScanner.php';
require_once __DIR__ . '/scanner/CertTransScanner.php';

require_once __DIR__ . '/model/Asset.php';
require_once __DIR__ . '/model/Errors.php';
require_once __DIR__ . '/model/Stats.php';
require_once __DIR__ . '/model/ScanJob.php';
require_once __DIR__ . '/model/Subdomain.php';
require_once __DIR__ . '/model/WHOISRecord.php';
require_once __DIR__ . '/model/DNSRecord.php';

require_once __DIR__ . '/storage/mysql/AssetMySQLRepository.php';
require_once __DIR__ . '/storage/mysql/ScanMySQLRepository.php';

require_once __DIR__ . '/service/AssetService.php';
require_once __DIR__ . '/service/ScanService.php';

require_once __DIR__ . '/validator/AssetValidator.php';

// Load .env file automatically
loadEnv(__DIR__ . '/../.env.docker');
