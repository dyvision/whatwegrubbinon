function profile() {
    var profile = gapi.auth2.init().currentUser.get().getBasicProfile();
}