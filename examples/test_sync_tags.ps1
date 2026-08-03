$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot ".env"
$payloadFile = Join-Path $PSScriptRoot "sync_tags_payload.json"

if (-not (Test-Path $envFile)) {
    throw "Cannot find .env file: $envFile"
}

$tokenLine = Get-Content $envFile |
    Where-Object {
        $_ -match '^\s*PLC_COLLECTOR_TOKEN_M62\s*='
    } |
    Select-Object -First 1

if (-not $tokenLine) {
    throw "PLC_COLLECTOR_TOKEN_M62 was not found in .env"
}

$token = ($tokenLine -split '=', 2)[1].Trim().Trim('"').Trim("'")

$url = "http://127.0.0.1:8000/api/collector/sync-tags"

$response = Invoke-RestMethod `
    -Method Post `
    -Uri $url `
    -Headers @{
        Authorization = "Bearer $token"
        Accept = "application/json"
    } `
    -ContentType "application/json; charset=utf-8" `
    -Body (Get-Content $payloadFile -Raw -Encoding UTF8)

$response | ConvertTo-Json -Depth 10
