function onSignIn(googleUser) {
    //quickly reference the logged in google profile
    var user = gapi.auth2.init().currentUser.get().getBasicProfile();
    var id = user.getId();
    document.cookie = "id=" + id;
    return user;
}

function logout() {
    document.cookie = "id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    gapi.auth2.getAuthInstance().disconnect();
    document.location.reload();
}