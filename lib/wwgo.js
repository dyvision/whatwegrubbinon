function onSignIn(googleUser) {
  //quickly reference the logged in google profile
  var user = gapi.auth2
    .init()
    .currentUser.get()
    .getBasicProfile();
  var id = user.getId();
  document.cookie = "id=" + id;
  return user;
}

function logout() {
  var cookies = document.cookie.split(";");

  for (var i = 0; i < cookies.length; i++) {
    var cookie = cookies[i];
    var eqPos = cookie.indexOf("=");
    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
  }
  document.location.reload();
}

function add_recipe(rid, id, guid) {
  var b64 = btoa(id + ":" + guid);
  var body = JSON.stringify({ rid: rid});
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.open("PUT", "api/recipe.php", true);
  xmlhttp.setRequestHeader("Authorization", "Basic " + b64);
  xmlhttp.setRequestHeader("Content-Type", "application/json");
  xmlhttp.onreadystatechange = function() {
    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
    }
  };
  xmlhttp.send(body);
  window.location.reload()
}
