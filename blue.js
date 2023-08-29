
   // UUID du service Tacx Flux 2
    const SERVICE_UUID = '6e40fec1-b5a3-f393-e0a9-e50e24dcca9e';

// UUID de la caractéristique de données de vitesse
    const CHARACTERISTIC_DATA_UUID = '6e40fec3-b5a3-f393-e0a9-e50e24dcca9e';

// Connecter à l'appareil Tacx Flux 2
    navigator.bluetooth.requestDevice({ filters: [{ namePrefix: 'Tacx Flux-2' }] })
        .then(device => {
            console.log('Appareil Tacx Flux 2 trouvé :', device.name);

            // Connecter à l'appareil
            return device.gatt.connect();
        })
        .then(server => {
            console.log('Connecté au serveur GATT de l\'appareil');

            // Obtenir le service Tacx Flux 2
            return server.getPrimaryService(SERVICE_UUID);
        })
        .then(service => {
            console.log('Service Tacx Flux 2 trouvé');

            // Obtenir la caractéristique de données de vitesse
            return service.getCharacteristic(CHARACTERISTIC_DATA_UUID);
        })
        .then(dataCharacteristic => {
            console.log('Caractéristique de données de vitesse trouvée');

            // Lire périodiquement la valeur de la caractéristique de données de vitesse
            setInterval(() => {
                console.log("appel read");
                dataCharacteristic.readValue()
                    .then(value => {
                        // Extraire la vitesse de l'octet reçu (exemple avec un format de valeur simple de 8 bits)
                        const speed = value.getUint8(0);

                        console.log('Vitesse actuelle :', speed);
                    })
                    .catch(error => {
                        console.error('erreur :',error);
                    });

            }, 1000); // Lire la vitesse toutes les 1 seconde
        })
        .catch(error => {
            console.error('Erreur :', error);
        });