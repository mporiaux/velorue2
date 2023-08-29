const noble = require('noble');

const TACX_FLUX_S2_UUID = '0000fece-0000-1000-8000-00805f9b34fb';

noble.on('stateChange', function(state) {
    if (state === 'poweredOn') {
        noble.startScanning();
        console.log('Scanning for TACX Flux S 2...');
    } else {
        noble.stopScanning();
        console.log('Bluetooth is turned off or not available.');
    }
});

noble.on('discover', function(peripheral) {
    if (peripheral.uuid === TACX_FLUX_S2_UUID) {
        noble.stopScanning();
        console.log('TACX Flux S 2 found! Connecting...');

        peripheral.connect(function(error) {
            if (error) {
                console.log('Error connecting to TACX Flux S 2:', error);
                return;
            }

            console.log('Connected to TACX Flux S 2.');

            peripheral.discoverServices([], function(error, services) {
                if (error) {
                    console.log('Error discovering services:', error);
                    return;
                }

                services.forEach(function(service) {
                    console.log('Service found:', service.uuid);

                    service.discoverCharacteristics([], function(error, characteristics) {
                        if (error) {
                            console.log('Error discovering characteristics:', error);
                            return;
                        }

                        characteristics.forEach(function(characteristic) {
                            console.log('Characteristic found:', characteristic.uuid);
                            // Manipulate the characteristics as needed
                        });
                    });
                });
            });
        });

        peripheral.on('disconnect', function() {
            console.log('Disconnected from TACX Flux S 2.');
            process.exit(0);
        });
    }
});

