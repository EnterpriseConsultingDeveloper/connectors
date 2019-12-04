document.getElementById('submit').style.display = "none";
appId = document.FBApp.idapplication.value;

function resetForm() {
    document.FBApp.name.value = '';
    document.FBApp.pageid.value = '';
    document.FBApp.token.value = '';
    document.FBApp.submit.style.display = "none";
}

// This is called with the results from from FB.getLoginStatus().
function statusChangeCallback(response) {
    console.log('statusChangeCallback');
    console.log(response.authResponse);
    // The response object is returned with a status field that lets the
    // app know the current login status of the person.
    // Full docs on the response object can be found in the documentation
    // for FB.getLoginStatus().
    if (response.status === 'connected') {
        // Logged into your app and Facebook.
        testAPI(response.authResponse);
    } else if (response.status === 'not_authorized') {
        // The person is logged into Facebook, but not your app.
        document.getElementById('status').innerHTML = 'Please log ' +
            'into this app.';
        $('.comboPage').remove();
        resetForm();
    } else {
        // The person is not logged into Facebook, so we're not sure if
        // they are logged into this app or not.
        document.getElementById('status').innerHTML = 'Please log into Facebook.';
        $('.comboPage').remove();
        resetForm();
    }
}

// This function is called when someone finishes with the Login
// Button.  See the onlogin handler attached to it in the sample
// code below.
function checkLoginState() {
    FB.getLoginStatus(function (response) {
        statusChangeCallback(response);
    });
}

window.fbAsyncInit = function () {
    FB.init({
        appId: appId,
        xfbml: true,
        version: 'v3.2'
    });


    // Now that we've initialized the JavaScript SDK, we call
    // FB.getLoginStatus().  This function gets the state of the
    // person visiting this page and can return one of three states to
    // the callback you provide.  They can be:
    //
    // 1. Logged into your app ('connected')
    // 2. Logged into Facebook, but not your app ('not_authorized')
    // 3. Not logged into Facebook and can't tell if they are logged into
    //    your app or not.
    //
    // These three cases are handled in the callback function.

    FB.getLoginStatus(function (response) {
        statusChangeCallback(response);
    });

};

// Load the SDK asynchronously
(function (d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s);
    js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

// Here we run a very simple test of the Graph API after login is
// successful.  See statusChangeCallback() for when this call is made.
function testAPI(authResponse) {
    var channelType = document.getElementById('channel-name').innerText;
    console.log(channelType);
    FB.api('/me', function (response) {
        console.log(response);
        console.log('Successful login for: ' + response.name);
        document.getElementById('status').innerHTML =
            'Thanks for logging in, ' + response.name + '!';

        if(channelType == 'Profile') {
            document.FBApp.name.value = response.name + ' profile';
            document.FBApp.key.value = response.id;
            document.FBApp.longlivetoken.value = authResponse.accessToken;
            document.getElementById('submit').style.display = "block";
        }

    });


    if(channelType == 'Page') { // Page
        FB.api('/me/accounts', function (response) {
            console.log('accounts', JSON.stringify(response));
            var data = response.data;
            var strPageResult = '<div class="form-group"><div class="comboPage"><label class="control-label">Pages managed: </label><select name ="page" class="form-control customclass">';
            strPageResult += '<option></option>';
            for (var i in data) {
                var name = data[i].name;
                var id = data[i].id;
                var access_token = data[i].access_token;
                strPageResult += '<option data-fbdata-name="' + name + '" data-fbdata-id="' + id + '" data-fbdata-tk="' + access_token + '">' + name + '</option>';
            }
            document.getElementById('pages').innerHTML = strPageResult + '</option></select></div></div>';

            $('.customclass').on('change', function () {
                // console.log($(this).data());
                // alert($(this).data().fbdataId);

                if ($('.customclass option:selected').data().fbdataId != undefined) {
                    document.FBApp.name.value = $('.customclass option:selected').data().fbdataName;
                    document.FBApp.pageid.value = $('.customclass option:selected').data().fbdataId;
                    document.FBApp.token.value = $('.customclass option:selected').data().fbdataTk;

                    document.getElementById('submit').style.display = "block";
                } else {
                    resetForm()
                }
            });
        });
    }
}