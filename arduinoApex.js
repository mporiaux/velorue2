var myService = null;
var myServiceDir = null;
async function connectDir(){
  if(myServiceDir === null) {
    // Vérifier la compatibilité avec le Bluetooth
    if ('bluetooth' in navigator) {
      navigator.bluetooth.requestDevice({filters: [{services: ['0000ffe0-0000-1000-8000-00805f9b34fb']}]})
        .then(device => {
          // Connexion à l'appareil Bluetooth sélectionné
          return device.gatt.connect();
        })
        .then(server => {
          myService = server.getPrimaryService('0000ffe0-0000-1000-8000-00805f9b34fb');
          // Récupération du service GATT

          return myService;
        })
        .then(service => {
          // Activation des notifications pour la caractéristique souhaitée
          return service.getCharacteristic('0000ffe1-0000-1000-8000-00805f9b34fb');
        })
        .then(characteristic => {
          // Activation des notifications
          alert("récupération de la caractéristique de direction et activation des notifications");
          return characteristic.startNotifications();
        })
        .then(characteristic => {
          // Écoute des changements de valeur de la caractéristique

          characteristic.addEventListener('characteristicvaluechanged', event => {
            // Lecture de la valeur mise à jour
            let value = event.target.value;

            let dv = new DataView(value.buffer);
            const etat = dv.getUint8(0, true);
            console.log("état ="+etat);
            direction=etat-48;
            setDirection();
          });
        })
        .catch(error => {
          console.error('Erreur :', error);
        });
    } else {
      console.log('Le Bluetooth n\'est pas supporté par ce navigateur.');
    }
  }
  else return myService;
}
