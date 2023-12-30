function timer(ms) {
  return new Promise(res => setTimeout(res, ms));
}
async  function lectureInfos() {

    $.ajax({
      type: "GET",
      contentType: "application/json",
      url: "https://apex.oracle.com/pls/apex/wksp_demoporiaux1/velo/infos/",
      dataType: 'json',
      timeout: 100000
    }).done(function (data) {
      vit = data.vitesse;
      direction = data.direction;
      dur = data.durete;
      console.log("SUCCESS: ", data.vitesse + " " + data.direction + " " + data.durete);
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.log("erreur de réception " + jqXHR.responseText + " " + errorThrown);
      }
    );
}
  async function setPente() {
    $.ajax({
      type: "PUT",
      contentType: "application/json",
      url: "https://apex.oracle.com/pls/apex/wksp_demoporiaux1/velo/durete/",
      data: JSON.stringify({ "durete": dur}),
      timeout: 100000}).
    done(function (data) {
       console.log("SUCCESS: ", "ok");
    }).
    fail(function(jqXHR, textStatus, errorThrown) {
        console.log("erreur de réception "+jqXHR.responseText+" "+errorThrown);
      }
    );
  }

