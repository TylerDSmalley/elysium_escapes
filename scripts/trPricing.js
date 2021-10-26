//  Author: Tyler Smalley
//  filename: trPricing.js 

function calculateDestinationPrice() {
    addPackageOption();
    let nightRate = .22;
    let destination = document.getElementById('destinationOption');
    let pIndex = destination.selectedIndex;
    let destinationPrice = destination.options[pIndex].value;
    let quantity = document.getElementById('nightQuantity');
    let qIndex = quantity.selectedIndex;
    let nightOrdered = quantity.options[qIndex].value;
    document.getElementById('basePrice').value = (((destinationPrice * nightRate) * nightOrdered) + +destinationPrice).toFixed(2);

    calculateTotal();
}

function addPackageOption(packageOption) {
    let quantity = document.getElementById('nightQuantity');
    let qIndex = quantity.selectedIndex;
    let nightOrdered = quantity.options[qIndex].value;
    let selectedQuantity = document.getElementById('nightQuantity');
    let sum;

    if (selectedQuantity.selectedIndex === 0) {
        window.alert('Select the amount of nights in your stay');
        selectedQuantity.focus();
        document.getElementById("optionA").checked = false;
        document.getElementById("optionB").checked = false;
        document.getElementById("optionC").checked = false;
        document.getElementById("optionD").checked = false;
        return false;
    } else {
        let selectedOptions = new Array();
        let packageOptions = document.getElementById("packageOptions");
        let chks = packageOptions.getElementsByTagName("INPUT");

        for (let i = 0; i < chks.length; i++) {
            if (chks[i].checked) {
                selectedOptions.push(chks[i].value);
            }
        }

        if (selectedOptions.length > 0) {
            sum = sumStr(selectedOptions);
            console.log(sum);
            document.getElementById('packageOptionResult').value = (Number(sum) * Number(nightOrdered)).toFixed(2);
        }
    }

    calculateTotal();
}

function sumStr(selectedOptions) {
    let sum = selectedOptions.reduce(function(total, num) {
        return parseFloat(total) + parseFloat(num);
    });

    return sum;
}


function calculateTotal() {
    let priceValue = parseFloat(document.getElementById('basePrice').value);
    let shipValue = parseFloat(document.getElementById('packageOptionResult').value);

    document.getElementById('subtotal').value = (priceValue + shipValue).toFixed(2);

    const TAX_RATE = 0.09975;

    let taxValue = (priceValue + shipValue) * TAX_RATE;

    document.getElementById('tax').value = taxValue.toFixed(2);

    document.getElementById('totalPrice').value = (priceValue + shipValue + taxValue).toFixed(2);
}

function validateForm() {
    let selectedDestination = document.getElementById('destinationOption');
    let selectedQuantity = document.getElementById('nightQuantity');
    let selectedPackages = document.getElementById('packageOptionResult');

    if (selectedDestination.selectedIndex === 0) {
        window.alert('You must select a destination');
        selectedDestination.focus();
        return false;
    } else

    if (selectedQuantity.selectedIndex === 0) {
        window.alert('You must select the amount of nights in your stay');
        selectedQuantity.focus();
        return false;
    } else
    if (selectedPackages.value === '0.00') {
        window.alert('You must select a package option');
        selectedPackages.focus();
        return false
    } else {
        return true;
    }

}