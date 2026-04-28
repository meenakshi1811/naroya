<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/fav.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Payment Method Creation</title>
</head>
<body>
    <h1>Create Payment Method</h1>
    <form action="{{ route('payment.store') }}" method="POST" id="payment-form">
        @csrf
        <div id="card-element">
            <!-- Stripe Card Element will be inserted here -->
        </div>

        <!-- Used to display any errors -->
        <div id="card-errors" role="alert"></div>

        <button type="submit" id="submit">Create Payment Method</button>
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Your Stripe public key
        var stripe = Stripe("{{ config('services.stripe.key') }}");

        // Create an instance of Stripe Elements
        var elements = stripe.elements();

        // Create a card Element
        var card = elements.create("card");

        // Mount the card Element into the DOM
        card.mount("#card-element");

        // Get the form element
        var form = document.getElementById("payment-form");

        // Add event listener to form submission
        form.addEventListener("submit", function (event) {
            event.preventDefault();  // Prevent default form submission

            // Create a PaymentMethod with the card Element
            stripe.createPaymentMethod({
                type: "card",
                card: card,
            }).then(function (result) {
                if (result.error) {
                    // If there's an error, show it to the user
                    var errorElement = document.getElementById("card-errors");
                    errorElement.textContent = result.error.message;
                } else {
                    // If successful, send the PaymentMethod ID to the server
                    stripeTokenHandler(result.paymentMethod.id);
                }
            });
        });

        // Function to send the payment method ID to the server
        function stripeTokenHandler(paymentMethodId) {
            var form = document.getElementById("payment-form");

            // Create a hidden input to store the payment method ID
            var hiddenInput = document.createElement("input");
            hiddenInput.setAttribute("type", "hidden");
            hiddenInput.setAttribute("name", "payment_method_id");
            hiddenInput.setAttribute("value", paymentMethodId);

            // Append the hidden input to the form
            form.appendChild(hiddenInput);

            // Now submit the form
            form.submit();  // This will submit the form to your Laravel backend
        }
    </script>
</body>
</html>
