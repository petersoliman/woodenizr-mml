import validator from "validator";
import { vsprintf as sprintf } from "sprintf-js";
import { closest } from "../../helpers/dom";

document.querySelectorAll('[data-form-validate]').forEach((form) => {
  form.__proto__.__onValidateSuccess = function () {this.submit();};
  form.__proto__.__validateForm = function () { validateForm(this); };
  form.__proto__.__errors = {};
  const inputs = form.querySelectorAll('[data-validate-input]');
  const submitBtns = form.querySelectorAll('[type="submit"]');
  inputs.forEach((input) => {
      input.__proto__.__errors = [];
      input.addEventListener('change', () => {
          validateInput(input, form);
      });
  });
  submitBtns.forEach((submitBtn) => {
      submitBtn.addEventListener('click', (e) => {
          e.preventDefault();
          validateForm(form);
      });
  });
});

function validateInput(input, form) {
    const rules = (typeof input.dataset.formValidateInputRules !== 'undefined') ? input.dataset.formValidateInputRules.split("|") : [];
    const name = input.name;
    const value = input.value;
    const errors = [];
    const errorList = document.querySelector('[data-validate-input-errors="' + input.dataset.validateInput + '"]');
    const inputBox = closest(input, '[data-form-validate-input-box');

    if (errorList.children.length) {
        [].slice.call(errorList.children).forEach((oldError) => {
            oldError.remove();
        })
    }

    rules.forEach(rule => {
        const ruleName = rule.split(":")[0];
        const ruleValue = rule.split(":")[1];
        if (ruleName === "required") {
          if (input.type === "radio") {
            const radios = document.getElementsByName(name);
            let oneChecked = false;
            radios.forEach(radio => {
              if (radio.checked) {
                oneChecked = true;
              }
            });
            if (oneChecked === false) {
              errors.push(__("Required"));
            }
          } else if (input.type === "checkbox") {
            if (!input.checked) {
              errors.push(__("Required"));
            }
          } else {
            if (!value) {
              errors.push(__("Required"));
            }
          }
        }
        if (ruleName === "minChars" && value.length < ruleValue) {
          errors.push(sprintf(__(`This value is too short. It should have %s characters or more.`), ruleValue));
        }
        if (ruleName === "maxChars" && value.length > ruleValue) {
          errors.push(sprinf(__(`This value is too long. It should have %s characters or fewer.`), ruleValue));
        }
        if (ruleName === "min" && value < ruleValue) {
          errors.push(sprintf(__(`This value should be greater than or equal to %s.`), ruleValue));
        }
        if (ruleName === "max" && value > ruleValue) {
          errors.push(sprinf(__(`This value should be lower than or equal to %s.`), ruleValue));
        }
        if (ruleName === "email" && !validator.isEmail(value)) {
          errors.push(__("This value should be a valid email."));
        }
        if (ruleName === "numeric" && !validator.isNumeric(value)) {
          errors.push(__("This value should be a valid number."));
        }
        if (ruleName === "url" && !validator.isURL(value)) {
          errors.push(__("This value should be a valid url."));
        }
        if (ruleName === "phone" && !validator.isMobilePhone(value)) {
          errors.push(__("Enter a valid mobile number."));
        }
        if (ruleName === "emailOrPhone" && !(validator.isEmail(value) || validator.isMobilePhone(value))) {
          errors.push(__("Enter a valid email or mobile number."));
        }
        if (ruleName === "passwordMatch" && value != document.querySelector(ruleValue).value) {
          errors.push(__("Those passwords didn't match."));
        }
    });

    if (errors.length) {
        inputBox.classList.add('has-error');
        // show first error only
        const errorItem = document.createElement("li");
        errorItem.textContent = errors[0];
        errorList.appendChild(errorItem);
        // show all errors
        // errors.forEach((error) => {
        //     const errorItem = document.createElement("li");
        //     errorItem.textContent = error;
        //     errorList.appendChild(errorItem);
        // });
    } else {
        inputBox.classList.remove('has-error');
    }

    input.__errors = errors;

    return errors;
}

function validateForm(form) {
    const inputs = form.querySelectorAll('[data-validate-input]');
    const errors = [];
    inputs.forEach((input) => {
      const inputErrors = validateInput(input, form);
      if (inputErrors.length) {
          errors.push(inputErrors);
      }
    });
    if (errors.length == 0) {
      form.__onValidateSuccess();
    }
}