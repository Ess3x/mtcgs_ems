/**
 * C# MAC Address Validation Helper
 * 
 * This helper class should be used in your C# biometric application
 * to validate the device's MAC address before recording attendance
 */

using System;
using System.Net.Http;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Net.NetworkInformation;
using System.Text.RegularExpressions;
using System.Linq;

public class MacAddressValidator
{
    private readonly string _apiBaseUrl;
    private readonly HttpClient _httpClient;

    public MacAddressValidator(string apiBaseUrl = "http://127.0.0.1:8000/api")
    {
        _apiBaseUrl = apiBaseUrl;
        _httpClient = new HttpClient();
    }

    /// <summary>
    /// Get the computer's MAC address
    /// </summary>
    public string GetDeviceMacAddress()
    {
        try
        {
            var nics = NetworkInterface
                .GetAllNetworkInterfaces()
                .Where(nic => nic.NetworkInterfaceType != NetworkInterfaceType.Loopback)
                .Where(nic => nic.OperationalStatus == OperationalStatus.Up)
                .Where(nic => !string.IsNullOrWhiteSpace(nic.GetPhysicalAddress()?.ToString()))
                .Where(nic => !nic.Description.Contains("Virtual", StringComparison.OrdinalIgnoreCase))
                .Where(nic => !nic.Description.Contains("VMware", StringComparison.OrdinalIgnoreCase))
                .Where(nic => !nic.Description.Contains("Hyper-V", StringComparison.OrdinalIgnoreCase))
                .OrderBy(nic => nic.NetworkInterfaceType == NetworkInterfaceType.Wireless80211 ? 0 :
                               nic.NetworkInterfaceType == NetworkInterfaceType.Ethernet ? 1 : 2)
                .ThenBy(nic => nic.Description)
                .ToArray();

            foreach (var nic in nics)
            {
                var macAddress = nic.GetPhysicalAddress()?.ToString();
                if (string.IsNullOrWhiteSpace(macAddress) || macAddress.Length != 12)
                    continue;

                macAddress = Regex.Replace(
                    macAddress,
                    "(.{2})",
                    "$1:",
                    RegexOptions.None
                ).TrimEnd(':');

                return macAddress;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"Error getting MAC address: {ex.Message}");
        }

        return null;
    }

    /// <summary>
    /// Validate MAC address with the server
    /// </summary>
    public async Task<(bool IsValid, string Message, Dictionary<string, object> DeviceInfo)> ValidateMacAddressAsync(string macAddress = null)
    {
        try
        {
            // Get MAC address if not provided
            if (string.IsNullOrEmpty(macAddress))
            {
                macAddress = GetDeviceMacAddress();
            }

            if (string.IsNullOrEmpty(macAddress))
            {
                return (false, "Could not retrieve MAC address", null);
            }

            // Call API endpoint
            var request = new HttpRequestMessage(HttpMethod.Post, $"{_apiBaseUrl}/validate-mac-address")
            {
                Content = new StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { mac_address = macAddress }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                )
            };

            var response = await _httpClient.SendAsync(request);
            var content = await response.Content.ReadAsStringAsync();

            if (response.StatusCode == System.Net.HttpStatusCode.OK)
            {
                using (var doc = System.Text.Json.JsonDocument.Parse(content))
                {
                    var root = doc.RootElement;
                    
                    bool success = root.GetProperty("success").GetBoolean();
                    string message = root.GetProperty("message").GetString();
                    
                    var deviceInfo = new Dictionary<string, object>();
                    
                    if (root.TryGetProperty("device", out var deviceProp))
                    {
                        foreach (var property in deviceProp.EnumerateObject())
                        {
                            deviceInfo[property.Name] = property.Value.GetString();
                        }
                    }
                    
                    return (success, message, deviceInfo);
                }
            }
            else if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
            {
                using (var doc = System.Text.Json.JsonDocument.Parse(content))
                {
                    var root = doc.RootElement;
                    string message = root.GetProperty("message").GetString();
                    return (false, message, null);
                }
            }
            else
            {
                return (false, $"Server error: {response.StatusCode}", null);
            }
        }
        catch (Exception ex)
        {
            return (false, $"Error validating MAC address: {ex.Message}", null);
        }
    }

    /// <summary>
    /// Usage example in your biometric form
    /// </summary>
    public static async Task ExampleUsage()
    {
        var validator = new MacAddressValidator("http://127.0.0.1:8000/api");
        
        // Get this device's MAC address
        string deviceMac = validator.GetDeviceMacAddress();
        Console.WriteLine($"Device MAC Address: {deviceMac}");
        
        // Validate with server
        var (isValid, message, deviceInfo) = await validator.ValidateMacAddressAsync(deviceMac);
        
        if (isValid)
        {
            Console.WriteLine($"✓ Device authorized: {message}");
            Console.WriteLine($"  Device Name: {deviceInfo["device_name"]}");
            Console.WriteLine($"  Location: {deviceInfo["location"]}");
            
            // PROCEED with attendance recording
        }
        else
        {
            Console.WriteLine($"✗ Device not authorized: {message}");
            // BLOCK attendance recording
        }
    }
}

/**
 * INTEGRATION STEPS FOR YOUR C# APPLICATION:
 * 
 * 1. Add this validator to your biometric form
 * 2. Before recording attendance, call ValidateMacAddressAsync()
 * 3. Only proceed if isValid == true
 * 4. Store the returned device_id in your attendance record
 * 
 * Example in your biometric button click handler:
 * 
 *   private async void BiometricButton_Click(object sender, EventArgs e)
 *   {
 *       var validator = new MacAddressValidator("http://127.0.0.1:8000/api");
 *       var (isValid, message, deviceInfo) = await validator.ValidateMacAddressAsync();
 *       
 *       if (!isValid)
 *       {
 *           MessageBox.Show(message, "Device Not Authorized", MessageBoxButtons.OK, MessageBoxIcon.Error);
 *           return; // Don't proceed with fingerprint
 *       }
 *       
 *       // Get fingerprint
 *       string fingerprint = GetFingerprintFromScanner();
 *       
 *       // Send to attendance API with MAC address
 *       await SendAttendanceToServer(fingerprint, deviceInfo["device_id"]);
 *   }
 */
