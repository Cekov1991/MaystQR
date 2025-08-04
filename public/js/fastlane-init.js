async function initFastlane() {
    try {
        if (!window.paypal.Fastlane) {
            throw new Error("PayPal script loaded but no Fastlane module");
        }

        const {
            identity,
            profile,
            FastlanePaymentComponent,
            FastlaneWatermarkComponent,
        } = await window.paypal.Fastlane({
            // Add any configuration options here
        });

        const paymentComponent = await FastlanePaymentComponent();

        (
            await FastlaneWatermarkComponent({
                includeAdditionalInfo: true,
            })
        ).render("#watermark-container");

        // State variables
        let memberAuthenticatedSuccessfully;
        let email;
        let paymentToken;

        // Form elements
        const form = document.querySelector("form");
        const customerSection = document.getElementById("customer");
        const emailSubmitButton = document.getElementById("email-submit-button");
        const paymentSection = document.getElementById("payment");
        const checkoutButton = document.getElementById("checkout-button");
        let activeSection = customerSection;

        // Helper functions
        const setActiveSection = (section) => {
            activeSection.classList.remove("active");
            section.classList.add("active", "visited");
            activeSection = section;
        };

        const getAddressSummary = ({
            companyName,
            address: {
                addressLine1,
                addressLine2,
                adminArea2,
                adminArea1,
                postalCode,
                countryCode,
            } = {},
            name: { firstName, lastName, fullName } = {},
            phoneNumber: { countryCode: telCountryCode, nationalNumber } = {},
        }) => {
            const isNotEmpty = (field) => !!field;
            const summary = [
                fullName || [firstName, lastName].filter(isNotEmpty).join(" "),
                companyName,
                [addressLine1, addressLine2].filter(isNotEmpty).join(", "),
                [
                    adminArea2,
                    [adminArea1, postalCode].filter(isNotEmpty).join(" "),
                    countryCode,
                ]
                    .filter(isNotEmpty)
                    .join(", "),
                [telCountryCode, nationalNumber].filter(isNotEmpty).join(""),
            ];
            return summary.filter(isNotEmpty).join("\n");
        };


        const validateFields = (form, fields = []) => {
            if (fields.length <= 0) return true;

            let valid = true;
            const invalidFields = [];

            for (let i = 0; i < fields.length; i++) {
                const currentFieldName = fields[i];
                const currentFieldElement = form.elements[currentFieldName];
                const isCurrentFieldValid = currentFieldElement.checkValidity();

                if (!isCurrentFieldValid) {
                    valid = false;
                    invalidFields.push(currentFieldName);
                    currentFieldElement.classList.add("input-invalid");
                    continue;
                }

                currentFieldElement.classList.remove("input-invalid");
            }

            if (invalidFields.length > 0) {
                const [firstInvalidField] = invalidFields;
                form.elements[firstInvalidField].reportValidity();
            }

            return valid;
        };

        // Email submission logic
        emailSubmitButton.addEventListener("click", async () => {
            const isEmailValid = validateFields(form, ["email"]);
            if (!isEmailValid) return;

            emailSubmitButton.setAttribute("disabled", "");

            // Reset form & state
            email = form.elements["email"].value;
            form.reset();
            document.getElementById("email-input").value = email;
            paymentSection.classList.remove("visited", "pinned");

            memberAuthenticatedSuccessfully = undefined;
            paymentToken = undefined;

            // Render payment component
            paymentComponent.render("#payment-component");

            try {
                // Look up and authenticate Fastlane members
                const { customerContextId } = await identity.lookupCustomerByEmail(email);

                if (customerContextId) {
                    const authResponse = await identity.triggerAuthenticationFlow(customerContextId);
                    console.log("Auth response:", authResponse);

                    if (authResponse?.authenticationState === "succeeded") {
                        memberAuthenticatedSuccessfully = true;
                        paymentToken = authResponse.profileData.card;
                    }
                } else {
                    console.log("No customerContextId");
                }

                // Update form UI
                customerSection.querySelector(".summary").innerText = email;

                if (memberAuthenticatedSuccessfully) {
                    paymentSection.classList.add("pinned");
                    setActiveSection(paymentSection);
                }
            } finally {
                emailSubmitButton.removeAttribute("disabled");
            }
        });

        // Enable email button
        emailSubmitButton.removeAttribute("disabled");

        // Edit buttons
        document.getElementById("email-edit-button").addEventListener("click", () => setActiveSection(customerSection));


        document.getElementById("payment-edit-button").addEventListener("click", () => setActiveSection(paymentSection));

        // Checkout button
        checkoutButton.addEventListener("click", async () => {
            checkoutButton.setAttribute("disabled", "");

            try {
                // Get payment token
                paymentToken = await paymentComponent.getPaymentToken();
                console.log("Payment token:", paymentToken);

                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Send transaction details to backend
                const body = {
                    paymentToken,
                    amount: "100.00", // You can make this dynamic
                    _token: csrfToken
                };

                const response = await fetch("/fastlane/transaction", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify(body),
                });

                const { result, error } = await response.json();

                if (error) {
                    console.error(error);
                    alert("Payment failed: " + error);
                } else {
                    if (result.id) {
                        const message = `Order ${result.id}: ${result.status}`;
                        console.log(message);
                        alert(message);
                    } else {
                        console.error(result);
                    }
                }
            } finally {
                checkoutButton.removeAttribute("disabled");
            }
        });
    } catch (error) {
        console.error("Fastlane initialization error:", error);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFastlane);
} else {
    initFastlane();
}