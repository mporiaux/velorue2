const definitions = {
  windSpeed:      {resolution: 0.001,  unit: 'mps',  size: 2, min: -35.56, max: 35.56,  default: 2}, // 0,
  grade:          {resolution: 0.01,   unit: '%',    size: 2, min: -40,    max: 40,     default: 0},
  crr:            {resolution: 0.0001, unit: '',     size: 1, min: 0,      max: 0.0254, default: 0.004},
  windResistance: {resolution: 0.01,   unit: 'kg/m', size: 1, min: 0,      max: 1.86,   default: 0.51},
};
const opCode = 0x11;

const flags = {
  InstantaneousSpeed:    { flagBit:  0, present: 0 },
  MoreData:              { flagBit:  0, present: 0 },
  InstantaneousCandence: { flagBit:  2, present: 1 }, // bit 1, present 0
  AverageSpeed:          { flagBit:  1, present: 1 }, // bit 2, present 1
  AverageCandence:       { flagBit:  3, present: 1 },
  TotalDistance:         { flagBit:  4, present: 1 },
  ResistanceLevel:       { flagBit:  5, present: 1 },
  InstantaneousPower:    { flagBit:  6, present: 1 },
  AveragePower:          { flagBit:  7, present: 1 },
  ExpendedEnergy:        { flagBit:  8, present: 1 },
  HeartRate:             { flagBit:  9, present: 1 },
  MetabolicEquivalent:   { flagBit: 10, present: 1 },
  ElapsedTime:           { flagBit: 11, present: 1 },
  RemainingTime:         { flagBit: 12, present: 1 }
  // Reserved:              { flagBit: 13-15, present: null }
};
const fields = {
  Flags:                 { type: 'Uint16', size: 2, resolution: 1,
    unit: 'bit'     },
  InstantaneousSpeed:    { type: 'Uint16', size: 2, resolution: 0.01,
    unit: 'kph'     },
  AverageSpeed:          { type: 'Uint16', size: 2, resolution: 0.01,
    unit: 'kph'     },
  InstantaneousCandence: { type: 'Uint16', size: 2, resolution: 0.5,
    unit: 'rpm'     },
  AverageCandence:       { type: 'Uint16', size: 2, resolution: 0.5,
    unit: 'rpm'     },
  TotalDistance:         { type: 'Uint24', size: 3, resolution: 1,
    unit: 'm'       },
  ResistanceLevel:       { type: 'Uint16', size: 2, resolution: 1,
    unit: 'unitless'},
  InstantaneousPower:    { type: 'Uint16', size: 2, resolution: 1,
    unit: 'W'       },
  AveragePower:          { type: 'Uint16', size: 2, resolution: 1,
    unit: 'W'       },
  TotalEnergy:           { type: 'Int16',  size: 2, resolution: 1,
    unit: 'kcal'    },
  EnergyPerHour:         { type: 'Int16',  size: 2, resolution: 1, unit: 'kcal'    },
  EnergyPerMinute:       { type: 'Uint8',  size: 1, resolution: 1,
    unit: 'kcal'    },
  HeartRate:             { type: 'Uint16', size: 2, resolution: 1,
    unit: 'bpm'     },
  MetabolicEquivalent:   { type: 'Uint8',  size: 1, resolution: 1,
    unit: 'me'      },
  ElapsedTime:           { type: 'Uint16', size: 2, resolution: 1,
    unit: 's'       },
  RemainingTime:         { type: 'Uint16', size: 2, resolution: 1,
    unit: 's'       }
};

async function connect() {
  if ('bluetooth' in navigator) {
    navigator.bluetooth.requestDevice({filters: [{services: ['00001826-0000-1000-8000-00805f9b34fb']}]})
      .then(device => {
        return device.gatt.connect();
      })
      .then(server => {
        myService = server.getPrimaryService('00001826-0000-1000-8000-00805f9b34fb');
        return myService;
      })
      .then(service => {
        vitesseCharacteristic = service.getCharacteristic('00002ad2-0000-1000-8000-00805f9b34fb');
        alert("récupération de la caractéristique vitesse ");
        return vitesseCharacteristic;
      })
      .then(characteristic  => {
        return  characteristic.startNotifications();
      })
      .then(characteristic => {
        characteristic.addEventListener('characteristicvaluechanged', event => {
          let value = event.target.value;
          vit = readSpeed(new DataView(value.buffer));
        });
        return myService;
      })
      .then(service => {
        return service.getCharacteristic('00002ad9-0000-1000-8000-00805f9b34fb');
      })
      .then(characteristic=> {
        slopeCharacteristic =characteristic;
        alert("caractéristique pente définie");
        return characteristic.startNotifications();
      })
      .then(characteristic => {
        characteristic.addEventListener('characteristicvaluechanged', event => {
          let value = event.target.value;
        });
        return characteristic;
      })
      .then(characteristic => {
        let data = RequestControl();
        characteristic.writeValueWithResponse(data);
        console.log("request control exécuté");
      })
      .catch(error => {
        console.error('Erreur :', error);
      });
  }
}

function encode(grade) {
  const buffer = new ArrayBuffer(7);
  const view   = new DataView(buffer);
  view.setUint8(0, opCode, true);
  view.setInt16(1, 2000, true);
  view.setInt16(3, grade, true);
  view.setUint8(5, 40, true);
  view.setUint8(6, 51, true);
  return view.buffer;
}

async function setPente() {
  let slopeValue = dur;
  let data = encode(Math.floor(slopeValue*100));
  var view1 = new DataView(data);
  console.log('appel de writeValueWithResponse avec pour valeur : '+view1);
  return  slopeCharacteristic.writeValueWithResponse(data);
}
function readSpeed(dataview) {
  const flags = dataview.getUint16(0, true);
  const speed = dataview.getUint16(speedIndex(flags), true);
  return (speed * fields.InstantaneousSpeed.resolution);
}

function speedIndex(flags) {
  let i = fields.Flags.size;
  return i;
}

function RequestControl() {
  const opCode = 0x00;
  const length = 1;
  const buffer = new ArrayBuffer(length);
  const view   = new DataView(buffer);
  view.setUint8(0, opCode);
  return view.buffer;
}
