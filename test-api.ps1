# Test the API

# First, make sure you have a user in the database
# You can create one through the web interface at /register

Write-Host "=== Customer API Test Script ===" -ForegroundColor Green
Write-Host ""

# Configuration
$baseUrl = "http://localhost:8000"
$username = "admin@example.com"  # Change this to your user email
$password = "password"  # Change this to your password

try {
    # 1. Login and get JWT token
    Write-Host "1. Logging in..." -ForegroundColor Yellow
    $loginBody = @{
        email = $username
        password = $password
    } | ConvertTo-Json

    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/api/login" `
        -Method Post `
        -ContentType "application/json" `
        -Body $loginBody `
        -ErrorAction Stop

    $token = $loginResponse.token
    Write-Host "✓ Login successful! Token received." -ForegroundColor Green
    Write-Host "Token: $($token.Substring(0, 50))..." -ForegroundColor Gray
    Write-Host ""

    # Set headers for authenticated requests
    $headers = @{
        "Authorization" = "Bearer $token"
        "Content-Type" = "application/json"
    }

    # 2. Get all customers
    Write-Host "2. Getting all customers..." -ForegroundColor Yellow
    $customers = Invoke-RestMethod -Uri "$baseUrl/api/customers" `
        -Method Get `
        -Headers $headers `
        -ErrorAction Stop

    Write-Host "✓ Retrieved $($customers.count) customers" -ForegroundColor Green
    Write-Host ($customers | ConvertTo-Json -Depth 3) -ForegroundColor Gray
    Write-Host ""

    # 3. Create a new customer
    Write-Host "3. Creating a new customer..." -ForegroundColor Yellow
    $newCustomer = @{
        firstName = "Test"
        lastName = "Customer"
        email = "test.customer$(Get-Random)@example.com"
        phoneNumber = "+1234567890"
        address = "123 Test Street"
        city = "Test City"
        postalCode = "12345"
        country = "USA"
    } | ConvertTo-Json

    $createResponse = Invoke-RestMethod -Uri "$baseUrl/api/customers" `
        -Method Post `
        -Headers $headers `
        -Body $newCustomer `
        -ErrorAction Stop

    $customerId = $createResponse.data.id
    Write-Host "✓ Customer created with ID: $customerId" -ForegroundColor Green
    Write-Host ($createResponse | ConvertTo-Json -Depth 3) -ForegroundColor Gray
    Write-Host ""

    # 4. Get the newly created customer
    Write-Host "4. Getting customer #$customerId..." -ForegroundColor Yellow
    $customer = Invoke-RestMethod -Uri "$baseUrl/api/customers/$customerId" `
        -Method Get `
        -Headers $headers `
        -ErrorAction Stop

    Write-Host "✓ Customer retrieved successfully" -ForegroundColor Green
    Write-Host ($customer | ConvertTo-Json -Depth 3) -ForegroundColor Gray
    Write-Host ""

    # 5. Update the customer
    Write-Host "5. Updating customer #$customerId..." -ForegroundColor Yellow
    $updateData = @{
        phoneNumber = "+9876543210"
        city = "Updated City"
    } | ConvertTo-Json

    $updateResponse = Invoke-RestMethod -Uri "$baseUrl/api/customers/$customerId" `
        -Method Patch `
        -Headers $headers `
        -Body $updateData `
        -ErrorAction Stop

    Write-Host "✓ Customer updated successfully" -ForegroundColor Green
    Write-Host ($updateResponse | ConvertTo-Json -Depth 3) -ForegroundColor Gray
    Write-Host ""

    # 6. Delete the customer
    Write-Host "6. Deleting customer #$customerId..." -ForegroundColor Yellow
    $deleteResponse = Invoke-RestMethod -Uri "$baseUrl/api/customers/$customerId" `
        -Method Delete `
        -Headers $headers `
        -ErrorAction Stop

    Write-Host "✓ Customer deleted successfully" -ForegroundColor Green
    Write-Host ($deleteResponse | ConvertTo-Json -Depth 3) -ForegroundColor Gray
    Write-Host ""

    Write-Host "=== All tests passed! ===" -ForegroundColor Green

} catch {
    Write-Host "✗ Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Make sure:" -ForegroundColor Yellow
    Write-Host "1. The Symfony server is running (php bin/console server:start or symfony server:start)" -ForegroundColor Yellow
    Write-Host "2. You have a valid user account (update username/password in this script)" -ForegroundColor Yellow
    Write-Host "3. The database is properly configured and migrated" -ForegroundColor Yellow
}
