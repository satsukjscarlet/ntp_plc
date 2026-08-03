$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot ".env"
$payloadFile = Join-Path $PSScriptRoot "plc_data_payload.json"

if (-not (Test-Path $envFile)) {
    throw "Cannot find .env file: $envFile"
}

if (-not (Test-Path $payloadFile)) {
    throw "Cannot find payload file: $payloadFile"
}

$tokenLine = Get-Content $envFile |
    Where-Object {
        $_ -match '^\s*PLC_COLLECTOR_TOKEN_M62\s*='
    } |
    Select-Object -First 1

if (-not $tokenLine) {
    throw "PLC_COLLECTOR_TOKEN_M62 was not found in .env"
}

$token = ($tokenLine -split '=', 2)[1].Trim()
$token = $token.Trim('"').Trim("'")

if ([string]::IsNullOrWhiteSpace($token)) {
    throw "PLC_COLLECTOR_TOKEN_M62 is empty"
}

$url = "http://127.0.0.1:8000/api/collector/plc-data"

$headers = @{
    Authorization = "Bearer $token"
    Accept = "application/json"
}

$body = Get-Content $payloadFile -Raw -Encoding UTF8

Write-Host "ENV file: $envFile"
Write-Host "Payload file: $payloadFile"
Write-Host "URL: $url"
Write-Host "Token length: $($token.Length)"
Write-Host "Sending request..."

try {
    $response = Invoke-RestMethod `
        -Method Post `
        -Uri $url `
        -Headers $headers `
        -ContentType "application/json; charset=utf-8" `
        -Body $body

    Write-Host "API request successful."
    $response | ConvertTo-Json -Depth 10
}
catch {
    Write-Host "API request failed."

    $statusCode = $null
    $responseText = $null

    if ($_.Exception.Response) {
        try {
            $statusCode = [int]$_.Exception.Response.StatusCode
        }
        catch {
            $statusCode = "Unknown"
        }

        try {
            $stream = $_.Exception.Response.GetResponseStream()
            if ($stream) {
                $reader = New-Object System.IO.StreamReader($stream)
                $responseText = $reader.ReadToEnd()
                $reader.Close()
            }
        }
        catch {
            $responseText = $null
        }
    }

    Write-Host "HTTP status: $statusCode"

    if ($responseText) {
        Write-Host "Laravel response:"
        Write-Host $responseText
    }
    else {
        Write-Host "Error message:"
        Write-Host $_.Exception.Message
    }

    exit 1
}
