// Biometric Fingerprint Simulator
// Para sa testing kahit walang actual device

class BiometricSimulator {
    constructor() {
        this.fakeFingerprints = {};
        this.init();
    }
    
    init() {
        console.log('Biometric Simulator Ready');
        this.loadStoredFingerprints();
    }
    
    loadStoredFingerprints() {
        const stored = localStorage.getItem('biometric_templates');
        if (stored) {
            this.fakeFingerprints = JSON.parse(stored);
        }
    }
    
    saveFingerprints() {
        localStorage.setItem('biometric_templates', JSON.stringify(this.fakeFingerprints));
    }
    
    // Simulate fingerprint capture
    captureFingerprint(employeeId, callback) {
        // Simulate scanner delay
        setTimeout(() => {
            const fakeData = btoa('fake_fingerprint_' + employeeId + '_' + Date.now());
            callback(fakeData);
        }, 500);
    }
    
    // Register fingerprint for employee
    registerFingerprint(employeeId, employeeName, callback) {
        this.captureFingerprint(employeeId, (fingerprintData) => {
            this.fakeFingerprints[employeeId] = {
                employee_id: employeeId,
                employee_name: employeeName,
                fingerprint_data: fingerprintData,
                registered_at: new Date().toISOString()
            };
            this.saveFingerprints();
            callback(true, fingerprintData);
        });
    }
    
    // Verify fingerprint
    verifyFingerprint(callback) {
        this.captureFingerprint('verify', (fingerprintData) => {
            // Find matching fingerprint
            let matchedEmployee = null;
            for (const empId in this.fakeFingerprints) {
                if (this.fakeFingerprints[empId].fingerprint_data === fingerprintData) {
                    matchedEmployee = this.fakeFingerprints[empId];
                    break;
                }
            }
            callback(matchedEmployee, fingerprintData);
        });
    }
    
    // Get all registered fingerprints
    getRegisteredFingerprints() {
        return Object.values(this.fakeFingerprints);
    }
}

// Initialize
window.biometricSimulator = new BiometricSimulator();
