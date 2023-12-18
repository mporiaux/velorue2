var file;
var kmlfile;
function previewFile() {

  const [file] = document.querySelector("input[type=file]").files;
  const reader = new FileReader();

  reader.addEventListener(
    "load",
    () => {

      this.extractGoogleCoords(reader.result)

    },
    false
  );

  if (file) {
    reader.readAsText(file);
    this.kmlfile=file;
    uploadFile();

    ctaLayer = new google.maps.KmlLayer({
      map: map,
      url: "https://moodle.lestrade.be/velorue/"+this.kmlfile.name
    })
    kml=true;
    alert("affichage du track : "+this.kmlfile.name);
  }
}

function affTrack(){
  ctaLayer = new google.maps.KmlLayer({
    map: map,
    url: "https://moodle.lestrade.be/velorue/"+this.kmlfile.name
  })
}

fileInput.addEventListener("change", function() {
  previewFile();
});

function uploadFile() {

  var formData = new FormData();
  formData.append("file", this.kmlfile);
  var xhttp = new XMLHttpRequest();

  // Set POST method and ajax file path
  xhttp.open("POST", "upload.php", true);

  // call on request changes state
  xhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {

      var response = this.responseText;
      if (response == 1) {
        alert("upload réussi");

      } else {
        alert("échec de l'upload");
      }
    }
  };

  // Send request with data
  xhttp.send(formData);
}

async function extractGoogleCoords(plainText) {
  let n=0;
  let parser = new DOMParser()
  let xmlDoc = parser.parseFromString(plainText, "text/xml")

  if (xmlDoc.documentElement.nodeName == "kml") {

    for (const item of xmlDoc.getElementsByTagName('Placemark')) {

      let markers = item.getElementsByTagName('LineString')

      /** MARKER PARSE **/
      for (const marker of markers) {

        var coords = marker.getElementsByTagName('coordinates')[0].childNodes[0].nodeValue.trim()
        coordBlank = coords.split(" ");
        if(coordBlank.length > 3) {
          let coordVirg = coordBlank[0].split(",");

          document.getElementById("lat").setAttribute("value", coordVirg[1]);
          document.getElementById("long").setAttribute("value", coordVirg[0]);
        }

      }
    }

  } else {
    throw "error while parsing"
  }
}

