// Form
let submitted = false;
const params = new URLSearchParams(window.location.search);
const form = params.get('form') ?? 'hdb';
console.log('form', form)

document.addEventListener("DOMContentLoaded", function () {
    fetch("https://www.cloudflare.com/cdn-cgi/trace")
    .then(res => res.text())
    .then(text => {
        const ip = text.match(/ip=(.*)/)[1].trim();
        document.querySelectorAll('input[name="ip"]').forEach(el => {
        el.value = ip;
        });
    })
    .catch(err => console.error("IP fetch error:", err));
});

$(document).ready(function () {
    // Initial load
    if (form === 'condo') {
        $('#project').select2({
            placeholder: 'Select project',
            allowClear: true,
        });

        $('select[name="project"], input[name="block"], select[name="sell"], input[name="floor"], input[name="unit"]').on('change', function () {
            if (submitted) {
                validateStep();
            }
        })

        fetch("../util_data.php?action=getProjects")
            .then(response => response.json())
            .then(data => {
                const projectSelect = $('select[name="project"]');
                projectSelect.empty();
                projectSelect.append('<option value="">Select project</option>');

                data.forEach(item => {
                    projectSelect.append(`<option value="${item.project}">${item.project}</option>`);
                });
            })
            .catch(error => console.error("Error loading project:", error));
    } else if (form === 'hdb') {
        $('#town').select2({
            placeholder: 'Select town',
            allowClear: true,
        })

        $('#street').select2({
            placeholder: 'Select street',
            allowClear: true,
        })

        $('#hdb-block').select2({
            placeholder: 'Select block',
            allowClear: true,
        });

        $('select[name="town"], select[name="street"], select[name="block"], select[name="flat_type"], select[name="sell"], input[name="floor"], input[name="unit"]').on('change', function () {
            if (submitted) {
                validateStep();
            }
        })

        fetch("../util_data.php?action=getTowns")
            .then(response => response.json())
            .then(data => {
                const townSelect = $('select[name="town"]');
                townSelect.empty();
                townSelect.append('<option value="">Select town</option>');

                data.forEach(item => {
                    townSelect.append(`<option value="${item.town}" data-id="${item.id}">${item.town}</option>`);
                });
            })
            .catch(error => console.error("Error loading towns:", error));

        $('select[name="town"]').on('change', function () {
            const town_id = $(this).find(':selected').data('id');
            if (!town_id) return;

            fetch(`../util_data.php?action=getStreet&town_id=${town_id}`)
                .then(response => response.json())
                .then(data => {
                    const streetSelect = $('select[name="street"]');
                    streetSelect.empty().append('<option value="">Select street</option>');
                    data.forEach(item => {
                        streetSelect.append(`<option value="${item.street_names}">${item.street_names}</option>`);
                    });
                })
                .catch(error => console.error("Error loading street:", error));

            fetch(`../util_data.php?action=getBlocks&town_id=${town_id}`)
                .then(response => response.json())
                .then(data => {
                    const blockSelect = $('select[name="block"]');
                    blockSelect.empty().append('<option value="">Select block</option>');
                    data.forEach(item => {
                        blockSelect.append(`<option value="${item.blocks}">${item.blocks}</option>`);
                    });
                })
                .catch(error => console.error("Error loading blocks:", error));
        });
    } else if (form == 'contact') {
        const form_type = params.get("form_type") || "hdb";
        initialTab(form_type);
        const formProperty = document.getElementById('form-property');

        params.forEach((value, key) => {
            if (key == 'form') {
                return;
            }

            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = key;
            hiddenInput.value = value;

            formProperty.appendChild(hiddenInput);
        })
    }

    $('.button-next').on('click', function () {
        console.log('next'. form)
        submitted = true;
        if (!validateStep()) {
            return;
        }

        let url = "?form=contact";
        url += `&form_type=${encodeURIComponent(form)}`
        if (form === 'condo') {
            const project = $('select[name="project"]').val();
            const block = $('#condo-block').val();
            const sell = $('#condo-sell').val();
            const floor = $('#condo-floor').val();
            const unit = $('#condo-unit').val();
            url += `&project=${encodeURIComponent(project)}`
            url += `&block=${encodeURIComponent(block)}`
            url += `&sell=${encodeURIComponent(sell)}`
            url += `&floor=${encodeURIComponent(floor)}`
            url += `&unit=${encodeURIComponent(unit)}`
        } else if (form === 'hdb') {
            const town = $('select[name="town"]').val();
            const street = $('select[name="street"]').val();
            const block = $('#hdb-block').val();
            const flat_type = $('select[name="flat_type"]').val();
            const sell = $('#hdb-sell').val();
            const floor = $('#hdb-floor').val();
            const unit = $('#hdb-unit').val();
            url += `&town=${encodeURIComponent(town)}`
            url += `&street=${encodeURIComponent(street)}`
            url += `&block=${encodeURIComponent(block)}`
            url += `&flat_type=${encodeURIComponent(flat_type)}`
            url += `&sell=${encodeURIComponent(sell)}`
            url += `&floor=${encodeURIComponent(floor)}`
            url += `&unit=${encodeURIComponent(unit)}`
        }
        window.location.href = url;
    });

    $('input[name="name"], input[name="ph_number"], input[name="email"]').on('input', function () {
        if (submitted) {
            formValidation();
        }
    });

    $('input[name="name"], input[name="block"]').on('input', function () {
        this.value = this.value.replace(/[^a-zA-Z0-9 ]/g, '');
    });

    $('input[name="ph_number"]').on('input', function () {
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    $('input[name="floor"], input[name="unit"], input[name="sqft"], input[name="ph_number"]').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('#submit-form').on('submit', function (event) {
        event.preventDefault();

        console.log("submit")

        // Validate form fields
        if (!formValidation()) {
            return;
        }

        this.submit();
    });
});

function validateStep() {
    let isValid = true
    $('.error-message').text('');

    if (form === 'condo') {
        const project = $('select[name="project"]').val();
        if (!project) {
            $('.project-error').text('Please select project');
            isValid = false;
        }

        const block = $('#condo-block').val();
        if (!block) {
            $('.block-error').text('Please enter block');
            isValid = false;
        }

        const sell = $('#condo-sell').val();
        if (!sell) {
            $('.sell-error').text('Please select an option.');
            isValid = false;
        }

        const floor = $('#condo-floor').val();
        if (!floor) {
            $('.floor-error').text('Please enter floor');
            isValid = false;
        }
        if (floor && floor > 50) {
            $('.floor-error').text('Please enter a floor number less than 50');
            isValid = false;
        }

        const unit = $('#condo-unit').val();
        if (!unit) {
            $('.unit-error').text('Please enter unit');
            isValid = false;
        }
        if (unit && (unit.length < 2 || unit.length > 4)) {
            $('.unit-error').text('Please enter a number between 2 and 4 digits');
            isValid = false;
        }
    } else if (form === 'hdb') {
        const town = $('select[name="town"]').val();
        if (!town) {
            $('.town-error').text('Please select an option.');
            isValid = false;
        }

        const street = $('select[name="street"]').val();
        if (!street) {
            $('.street-error').text('Please select street');
            isValid = false;
        }

        const block = $('#hdb-block').val();
        if (!block) {
            $('.block-error').text('Please select block');
            isValid = false;
        }

        const flatType = $('select[name="flat_type"]').val();
        if (!flatType) {
            $('.flat_type-error').text('Please select an option.');
            isValid = false;
        }

        const sell = $('#hdb-sell').val();
        if (!sell) {
            $('.sell-error').text('Please select an option.');
            isValid = false;
        }

        const floor = $('#hdb-floor').val();
        if (!floor) {
            $('.floor-error').text('Please enter floor');
            isValid = false;
        }
        if (floor && floor > 50) {
            $('.floor-error').text('Please enter a floor number less than 50');
            isValid = false;
        }

        const unit = $('#hdb-unit').val();
        if (!unit) {
            $('.unit-error').text('Please enter unit');
            isValid = false;
        }
        if (unit && (unit.length < 2 || unit.length > 4)) {
            $('.unit-error').text('Please enter a number between 2 and 4 digits');
            isValid = false;
        }
    }

    return isValid;
}

function formValidation() {
    console.log('form validation')
    let validFields = true;
    $('.error-message').text('');

    // Validate name
    const name = $('input[name="name"]').val();
    if (!name) {
        $('.name-error').text('Please enter your name.');
        validFields = false;
    }

    // Validate email
    const email = $('input[name="email"]').val();
    if (!email) {
        $('.email-error').text('Please enter your email address.');
        validFields = false;
    }
    if (email && !validateEmail(email)) {
        $('.email-error').text('Please enter a valid email address.');
        validFields = false;
    }

    // Validate phone number
    const phoneNumber = $('input[name="ph_number"]').val();
    if (!phoneNumber) {
        $('.ph_number-error').text('Please enter your phone number.');
        validFields = false;
    }
    if (phoneNumber && !validatePhoneNumber(phoneNumber)) {
        $('.ph_number-error').text('Must exactly 8 digits & start with 8 or 9 (valid Singapore mobile numbers)');
        validFields = false;
    }

    // Validate checkbox
    const isChecked = $('input[name="terms"]').is(':checked');
    if (!isChecked) {
        $('.terms-error').text('You must agree to the terms and conditions.');
        validFields = false;
    }

    return validFields;
}

function validateEmail(email) {
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return emailPattern.test(email);
}

function validatePhoneNumber(phoneNumber) {
    const phonePattern = /^[89]\d{7}$/;
    return phonePattern.test(phoneNumber);
}

// Base
const names = [
    "Emily Tan Wei Ling",
    "Brandon Ng Jia Hao",
    "Siti Nur Aisyah Binti Ahmad",
    "Darren Lim Xin Rui",
    "Nurul Izzati Binti Hassan",
    "Rachel Wong Yi Xuan",
    "Muhammad Faris Bin Zulkifli",
    "Sophia Lee Mei Zhen",
    "Hafiz Rahman Bin Ismail",
    "Chloe Cheng Yu Xuan"
];

let currentIndex = 0;

function showPopup() {
    const popupContent = document.querySelector('.popup-content');
    const name = names[currentIndex];
    // Only show the name with 'submitted recently' text
    popupContent.innerHTML = `${name}<br> just submitted recently`;

    // Show the popup
    const popup = document.querySelector('.popup');
    popup.style.opacity = '1';

    // Hide the popup after 5 seconds
    setTimeout(() => {
        popup.style.opacity = '0';
        currentIndex = (currentIndex + 1) % names.length; // Move to the next name
    }, 5000);
}

// Start showing popups every 10 seconds
setInterval(showPopup, 10000); // Every 10 seconds (5 seconds display + 5 seconds wait)

// Initial call to show the first popup immediately
showPopup();

const tabs = document.querySelectorAll(".tab");
const formSections = {
    hdb: document.getElementById("form-hdb"),
    condo: document.getElementById("form-condo"),
    contact: document.getElementById("form-contact"),
};

function initialTab(form) {
    console.log('initial tab')
    tabs.forEach((tab) => {
        if (tab.getAttribute("data-form") === form ) {
            tab.classList.add("active");
        } else {
            tab.classList.remove("active");
        }
    });
}

function showForm(formType) {
    Object.keys(formSections).forEach((type) => {
        formSections[type].style.display =
            type === formType ? "block" : "none";
    });
}

document.addEventListener("DOMContentLoaded", function () {
    if (form !== "contact") {
        initialTab(form);
    }
    showForm(form);
});
