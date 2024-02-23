/** 
 * Update Subscription payment method
*/

var ncs = {};
var cardNumber = '';
var stripe = '';
var buttonElem = document.getElementById("sc_update_card_button");

jQuery('#update-card').click(function (e) {

    e.preventDefault();
    stripe = Stripe(stripe_key[0]);
    if(jQuery("#updateCard").length) {
        cardNumber = ncs.mountStripeCard();
    }
    jQuery('.update-card-modal').addClass('opened');
    

});

jQuery('.closemodal').click(function (e) {
    e.preventDefault();
    jQuery('.update-card-modal').removeClass('opened');
});


ncs.mountStripeCard = function(){
   
    var elements = stripe.elements();
    var elementStyles = {
        base: {
            color: "#6F6F6F",
            lineHeight: "25px",
        },

        invalid: {
            color: "#E25950",
            "::placeholder": {
                color: "#FFCCA5",
            },
        },
    };
      
    // Mount Card inputs to dom elements
    var cardNumber = elements.create("cardNumber", { style: elementStyles });
    cardNumber.mount("#card-number");
    
    var cardExpiry = elements.create("cardExpiry", { style: elementStyles });
    cardExpiry.mount("#card-expiry");
    
    var cardCvc = elements.create("cardCvc", { style: elementStyles });
    cardCvc.mount("#card-cvc");
    
    // Display Card error
    cardNumber.addEventListener("change", function (event) {
        ncs.validateCard(event, "card-error", "card-number");
    });
    
    cardCvc.addEventListener("change", function (event) {
        ncs.validateCard(event, "cvc-error", "card-cvc");
    });
    
    cardExpiry.addEventListener("change", function (event) {
        ncs.validateCard(event, "expiry-error", "card-expiry");
    });
    
    return cardNumber;
};

ncs.updatePaymentMethod = function (cardNumber) {
   
    const cardHolderName = document.getElementById("cardHolderName").value;
   
    stripe.createPaymentMethod({
        type: "card",
        card: cardNumber,
        billing_details: {
            name: cardHolderName,
        },
    }).then((result) => {

        if(result.error){
            ncs.hideLoader(buttonElem);
            
            if (result.error.code == "incomplete_number") {}
            if (result.error.code == "incomplete_expiry") {}
            if (result.error.code == "incomplete_cvc") {}
            if (result.error.code == "card_declined"){
                document.getElementById("card-error").innerHTML = result.error.message;
                document.getElementById("card-error").classList.add("error-label");
            }
        } else {
           
            ncs.updateSubscription(result.paymentMethod.id);
        }
    });
};

ncs.updateSubscription = function (payment_method) {

    var subscription_id = jQuery("#sc-subscription-id").val();

    jQuery.ajax({
        type: "post",
        dataType: "json",
        url: studiocart.ajax,
        data: { 
            action: "update_stripe_payment_method", 
            payment_method: payment_method,
            post_id: subscription_id,
        },
        success: function (response) {

            ncs.hideLoader(buttonElem);
            console.log(response);

            if ('undefined' !== typeof response.error) {

                alert(response.error);
                return false;

            }else{

                document.getElementById('successMsg').innerHTML = response.message;
                let url = location.pathname + location.search.replace(/[\?&]action=[^&]+/, '').replace(/^&/, '?')
                setTimeout(function(){ window.location.href = url; }, 3000);
            }
        },
    });
};

ncs.validateCard = function (event, errElemId, inputElemId) {

    var cardError = document.getElementById(errElemId);
    var cardInput = document.getElementById(inputElemId);

    if (event.error) {
        cardError.innerHTML = event.error.message;
        cardError.classList.add("error-label");
        cardInput.classList.add("error-border");
    } else {
        cardError.innerHTML = "";
        cardInput.classList.remove("error-border");
        cardInput.classList.remove("stripe-error");
    }
};

ncs.validateCardHolder = function(){
    var cardHolderName = document.getElementById("cardHolderName").value;
    var carHolderNameInput = document.getElementById('cardHolderName');
    var cardHolderErrElem = document.getElementById("cardholder-error");

    if(cardHolderName===""){
        ncs.hideLoader(buttonElem);
        cardHolderErrElem.classList.add("error-label");
        cardHolderErrElem.innerHTML = 'Please enter card holder name.'
        carHolderNameInput.classList.add("error-border");

    }else{

        document.getElementById("cardholder-error").innerHTML = '';
        cardHolderErrElem.classList.remove("error-label");
        carHolderNameInput.classList.remove("error-border");

    }
}

ncs.showLoader = function(elem =''){
    document.getElementById("sc-preloader").style.display = 'block';
    if(elem){
        elem.setAttribute('disabled','disabled');
    }
}

ncs.hideLoader = function(elem = ''){
    document.getElementById("sc-preloader").style.display = 'none';
    if(elem){
        elem.removeAttribute('disabled');
    }
}


var updateCardForm = document.getElementById("updateCard");

if(updateCardForm){

    updateCardForm.addEventListener("submit", function(e) {
        e.preventDefault();
        ncs.showLoader(buttonElem);
        ncs.validateCardHolder();
        ncs.updatePaymentMethod(cardNumber);
    });
}

let searchParams = new URLSearchParams(window.location.search)
if(searchParams.has('sc-plan') && searchParams.has('action') && searchParams.get('action') == 'pay') {
    jQuery('#update-card').click();    
}
