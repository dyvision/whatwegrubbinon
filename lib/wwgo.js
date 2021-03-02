function onSignIn(googleUser) {
    //quickly reference the logged in google profile
    var user = gapi.auth2.init().currentUser.get().getBasicProfile();
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