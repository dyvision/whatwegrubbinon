function onSignIn(googleUser) {
    //quickly reference the logged in google profile
    var profile = gapi.auth2.init().currentUser.get().getBasicProfile();
    var id = profile.getId();
    document.cookie = "id=" + id;
    return profile;
}

function logout() {
    gapi.auth2.getAuthInstance().disconnect();
    document.cookie = "id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}