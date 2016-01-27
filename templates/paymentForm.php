<!---start of form-->
<span class='payment-errors required'></span>
<div class="form-row">
  <label>Card Number</label>
  <input class="cc-num" type="tel" data-stripe="number" autocomplete="cc-number">
</div>
<div class="form-row">
  <label>3-digit Security Code (CVC)</label>
  <input class="cc-cvc" type="text" data-stripe="cvc" autocomplete="off">
</div>
<div class="form-row">
  <label>Expiry Date (MM/YYYY)</label>
  <input class="cc-exp" type="text" data-stripe="exp" autocomplete="cc-exp">
</div>
  <input type="hidden" class="cc-exp-month" type="text" data-stripe="exp-month">
  <input type="hidden" class="cc-exp-year" type="text" data-stripe="exp-year">
  <input id="stripeTokenField" type="hidden" type="text" name="stripeToken" value="">
  <p>Note: This form processes payments securely via <a href="http://stripe.com" target="_blank">Stripe</a>. Your card details <strong>never</strong> hit our server</p>
<!---end of form-->
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-alpha1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.payment/1.3.2/jquery.payment.min.js"></script>
<script type="text/javascript">
  // This identifies your website in the createToken call below
  Stripe.setPublishableKey("<?php echo ($this->stripe_sandbox == 'yes' ? $this->stripe_testpublickey : $this->stripe_livepublickey);?>");
  // ...
</script>
<script>
  //Validation and formatting
  $(document).ready(function(){
    $('input.cc-num').payment('formatCardNumber');
    $('input.cc-exp').payment('formatCardExpiry');
    $('input.cc-cvc').payment('formatCardCVC');
  })
</script>
<script>
  //Inline validation of card details on form
var validateCardDetails = function(){
    var error_messages = [];
    var error_list = document.createElement('ul');

    var cardNumber = $('input.cc-num').val();
    var cvc = $('input.cc-cvc').val();
    var month = $('input.cc-exp-month').val();
    var year = $('input.cc-exp-year').val();

    var validNumber = $.payment.validateCardNumber(cardNumber);
    var validCVC = $.payment.validateCardCVC(cvc);
    var validExpiry = $.payment.validateCardExpiry(month, year);

    if(! validNumber){
      error_messages.push("Enter a valid card number");
    }
    if(! validCVC){
      error_messages.push("Enter a valid cvc number");
    }
    if(! validExpiry){
      error_messages.push("Enter a valid expiry date");
    }

    if(error_messages.length > 0){
      for(var index in error_messages){
        $(error_list).append('<li>' + error_messages[index] + '</li>');
      }
      $('.payment-errors').html($(error_list).html());
    }

    return (validNumber && validCVC && validExpiry);
  };


  //Form submission
  jQuery(function($) {
  $('form[name="checkout"]').bind('checkout_place_order', function(e){
    //only if this payment provider is selected...
    if($('div.payment_box.payment_method_stripe').is(':visible')){
      //if the token already exists, then submit the form as normal
      if($('#stripeTokenField').val().length > 0){
        return true;
      } else {
        //else get the token and resubmit
          //fill in expiry month and year
          var expiry = $('input.cc-exp').payment('cardExpiryVal');
          $('input.cc-exp-month').val(expiry.month);
          $('input.cc-exp-year').val(expiry.year);

          var $form = $(this);

          // Disable the submit button to prevent repeated clicks
          $form.find('button').prop('disabled', true);
          //validate before hitting stripe
          if(validateCardDetails()){
            Stripe.card.createToken($form, stripeResponseHandler);
          }
          // Prevent the form from submitting with the default action
          return false;
      }
    }
  });

});

  //On token creation and / or form errors
  function stripeResponseHandler(status, response) {
  var $form = $('form[name="checkout"]');

  if (response.error) {
    console.log(response.error);
    // Show the errors on the form
    $form.find('.payment-errors').text(response.error.message);
    $form.find('button').prop('disabled', false);
    return false;
  } else {
    // response contains id and card, which contains additional card details
    var token = response.id;
    // Insert the token into the form so it gets submitted to the server
    console.log(token);
    $('#stripeTokenField').val(token);
    //retriggers woocommerce form, this time it will have a token and will submit
    $('#place_order').trigger('click');
  }
};
</script>
