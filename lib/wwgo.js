function profile() {
    //quickly reference the logged in google profile
    var profile = gapi.auth2.init().currentUser.get().getBasicProfile();
    return profile;
}

function onSignIn(googleUser) {
    document.location.href = '/profile.php'
};