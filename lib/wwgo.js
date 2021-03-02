function onSignIn(googleUser) {
    //quickly reference the logged in google profile
    var profile = gapi.auth2.init().currentUser.get().getBasicProfile();
    var id = profile().getId();
    document.cookie = "id=" + id;
    return profile;
}