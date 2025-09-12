<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .green { color: #28a745; }
        .red { color: #dc3545; }
        .blue { color: #007bff; }
        .checkmark { margin-right: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🚀 Laravel Application Test</h1>
        <p>This is a simple test page to verify the Laravel application is working correctly.</p>
        
        <h2>System Status</h2>
        
        <div class="status">
            <span class="checkmark green">✓</span>
            <span class="green"><strong>Laravel Framework:</strong> {{ app()->version() }}</span>
        </div>
        
        <div class="status">
            <span class="checkmark green">✓</span>
            <span class="green"><strong>PHP Version:</strong> {{ PHP_VERSION }}</span>
        </div>
        
        <div class="status">
            <span class="checkmark green">✓</span>
            <span class="green"><strong>Environment:</strong> {{ app()->environment() }}</span>
        </div>
        
        <div class="status">
            <span class="checkmark green">✓</span>
            <span class="green"><strong>Current Time:</strong> {{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
        
        <div class="status">
            <span class="checkmark blue">ℹ</span>
            <span class="blue"><strong>App Name:</strong> {{ config('app.name') }}</span>
        </div>
        
        <div class="status">
            <span class="checkmark blue">ℹ</span>
            <span class="blue"><strong>App URL:</strong> {{ config('app.url') }}</span>
        </div>

        <h2>Database Connection Test</h2>
        @php
            try {
                $connection = DB::connection();
                $pdo = $connection->getPdo();
                $dbName = $connection->getDatabaseName();
                $dbConnected = true;
                $dbError = null;
            } catch (Exception $e) {
                $dbConnected = false;
                $dbError = $e->getMessage();
            }
        @endphp
        
        @if($dbConnected)
            <div class="status">
                <span class="checkmark green">✓</span>
                <span class="green"><strong>Database Connected:</strong> {{ $dbName }}</span>
            </div>
        @else
            <div class="status">
                <span class="checkmark red">✗</span>
                <span class="red"><strong>Database Error:</strong> {{ $dbError }}</span>
            </div>
        @endif

        <h2>Next Steps</h2>
        <ul>
            <li><a href="/">Go to Homepage</a></li>
            <li><a href="/login">Go to Login Page</a></li>
            <li><a href="/dashboard">Go to Dashboard</a> (requires login)</li>
        </ul>

        <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px;">
            <strong>✅ If you can see this page, Laravel is working correctly!</strong>
        </div>
    </div>
</body>
</html>