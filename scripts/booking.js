function checkPhone(phone) {
    let rep = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
    if (phone.match(rep)) {
        return true;
    } else {
        return false;
    }
}

function checkEmail(email) {
    let ree = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
    if (email.match(ree)) {
        return true;
    } else {
        return false;
    }
}

function checkPostalCode(post) {
    let repc = /^[ABCEGHJ-NPRSTVXY]\d[ABCEGHJ-NPRSTV-Z][ -]?\d[ABCEGHJ-NPRSTV-Z]\d$/i;
    if (post.match(repc)) {
        return true;
    } else {
        return false;
    }
}

function validateForm() {

    // declare variables
    var postalCode = document.getElementById('postal-code');
    var emailAddress = document.getElementById('email');
    var phoneNumber = document.getElementById('phone');
    var numOfAdults = document.getElementById('adults');
    var totalNights = document.getElementById('total');
    var numOfChildren = document.getElementById('children');
    var arrivalDate = new Date(document.getElementById('arrival').value);
    var departureDate = new Date(document.getElementById('departure').value);
    var today = new Date();
    
    // validate Email
    if (checkEmail(emailAddress.value) === false) {
        emailAddress.focus();
        window.alert('You must enter a valid email. Ex: name@domain.com');
        return false;
    } // end if 
    else
    // validate Phone Number
    if (checkPhone(phoneNumber.value) === false) {
        window.alert('"You must enter a valid phone number. Ex: 555-555-5555"');
        phoneNumber.focus();
        return false;
    } // end if 
    else
    //validate Postal Code
    if (checkPostalCode(postalCode.value) === false) {
        window.alert('You must enter a valid postal code. Ex: H1A 1C9');
        postalCode.focus();
        return false
    } else
    //validate at least 1 adult is selected
    if (numOfAdults.value < 1) {
        window.alert('Please select at least 1 adult');
        numOfAdults.focus();
        return false
    } else
    //validate at least 1 night stay is selected
    if (totalNights.value < 1) {
        window.alert('Please select at least 1 night stay');
        totalNights.focus();
        return false
    } else //validate arrival date is at least tomorrow or later. Not in past
    if(arrivalDate < today){
        window.alert('Please enter an arrival date of tomorrow or later in the future');
        document.getElementById('arrival').focus();
        return false
    }else//validate departure date is after arrival date
    if (departureDate <= arrivalDate) {
        window.alert('Please enter a departure date at least 1 day after arrival date');
        document.getElementById('departure').focus();
        return false
    } else {
        //Store Booking Details form data into session storage
        const bookingDetails = [];
        var location = document.getElementsByName('location');
        for (i = 0; i < location.length; i++) {
            if (location[i].checked)
                bookingDetails[0] = location[i].value;
        }
        bookingDetails.push(numOfAdults.value,
            numOfChildren.value,
            `${arrivalDate.getMonth() + "-" + arrivalDate.getDate() +"-" + arrivalDate.getUTCFullYear()}`,
            `${departureDate.getMonth() + "-" + departureDate.getDate() +"-" + departureDate.getUTCFullYear()}`,
            totalNights.value);
        sessionStorage.setItem("bookingDetails", JSON.stringify(bookingDetails));
        return true;
    }
}


function confirmContact() {
    if (document.getElementById('fullname').value === "" || document.getElementById('subject').value === "") {
        return false
    } else {
        document.getElementById('confirmMessage').innerHTML = "Thank you for contacting us.<br/> We will get back to you shortly!";
    }
}