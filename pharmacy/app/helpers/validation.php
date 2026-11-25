<?php

/**
 * validation.php
 *
 * Reads form fields safely and checks account details on the SERVER.
 * The browser also checks some fields, but anyone can switch that off,
 * so PHP must check everything again (SRS NFR Security: server-side validation).
 */

/**
 * Read one text field from a submitted form.
 *
 * If the field is missing, or someone sends a list instead of text,
 * an empty string is returned instead of causing a PHP error.
 *
 * @param string $fieldName The name="" of the form field.
 * @return string The text that was typed.
 */
function postValue(string $fieldName): string
{
    $value = $_POST[$fieldName] ?? '';

    if (!is_string($value)) {
        return '';
    }

    return $value;
}

/**
 * Read the fields of an account form (registration and admin setup) and tidy them.
 *
 * trim() removes spaces at the start and end.
 * Emails are saved in lowercase, so Ali@Gmail.com and ali@gmail.com are the same account.
 *
 * @return array name, email, phone, address, password, confirm_password
 */
function getAccountInput(): array
{
    return [
        'name' => trim(postValue('name')),
        'email' => strtolower(trim(postValue('email'))),
        'phone' => trim(postValue('phone')),
        'address' => trim(postValue('address')),
        'password' => postValue('password'),
        'confirm_password' => postValue('confirm_password'),
    ];
}

/**
 * Check a new password and its confirmation.
 * Used by registration, admin setup and password reset.
 *
 * @param string $password        The new password.
 * @param string $confirmPassword The same password typed again.
 * @return array Error messages by field name. Empty when everything is fine.
 */
function validateNewPassword(string $password, string $confirmPassword): array
{
    $errors = [];

    // FR-01: at least 8 characters. bcrypt only uses the first 72 bytes, so we stop there.
    if (strlen($password) < 8) {
        $errors['password'] = 'Your password must be at least 8 characters long.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Your password must be 72 characters or fewer.';
    }

    if ($confirmPassword !== $password) {
        $errors['confirm_password'] = 'The two passwords do not match.';
    }

    return $errors;
}

/**
 * Check every field of an account form.
 *
 * @param array $input     The tidy values from getAccountInput().
 * @param User  $userModel Used to check whether the email is already registered.
 * @return array Error messages by field name. Empty when everything is fine.
 */
function validateAccountInput(array $input, User $userModel): array
{
    $errors = [];

    // ---------- Full name (FR-01) ----------
    if ($input['name'] === '') {
        $errors['name'] = 'Please enter your full name.';
    } elseif (mb_strlen($input['name']) > 100) {
        $errors['name'] = 'Your name must be 100 characters or fewer.';
    }

    // ---------- Email (FR-01, FR-02) ----------
    $emailIsValid = (filter_var($input['email'], FILTER_VALIDATE_EMAIL) !== false);

    if ($input['email'] === '') {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!$emailIsValid || mb_strlen($input['email']) > 150) {
        $errors['email'] = 'Please enter a valid email address, for example ali@gmail.com.';
    } elseif ($userModel->emailExists($input['email'])) {
        $errors['email'] = 'An account with this email already exists. Try logging in instead.';
    }

    // ---------- Phone (FR-01) ----------
    // Allowed: digits, spaces, dashes and one + at the start. It must contain 10 to 15 digits.
    $phoneHasAllowedCharacters = (preg_match('/^\+?[0-9 \-]+$/', $input['phone']) === 1);
    $phoneDigitCount = strlen(preg_replace('/[^0-9]/', '', $input['phone']));

    if ($input['phone'] === '') {
        $errors['phone'] = 'Please enter your phone number.';
    } elseif (!$phoneHasAllowedCharacters || $phoneDigitCount < 10 || $phoneDigitCount > 15 || strlen($input['phone']) > 20) {
        $errors['phone'] = 'Please enter a valid phone number, for example 0300 1234567.';
    }

    // ---------- Password (FR-01) ----------
    $passwordErrors = validateNewPassword($input['password'], $input['confirm_password']);
    $errors = array_merge($errors, $passwordErrors);

    // ---------- Delivery address (optional) ----------
    if (mb_strlen($input['address']) > 500) {
        $errors['address'] = 'Your address must be 500 characters or fewer.';
    }

    return $errors;
}

/**
 * Read the fields of the profile form and tidy them (FR-61).
 * The email address is not here: it identifies the account and cannot be changed.
 *
 * @return array name, phone, address
 */
function getProfileInput(): array
{
    return [
        'name' => trim(postValue('name')),
        'phone' => trim(postValue('phone')),
        'address' => trim(postValue('address')),
    ];
}

/**
 * Check the fields of the profile form.
 *
 * These are the same rules the registration form uses, so a profile can never
 * hold something that would have been rejected at registration.
 *
 * @param array $input The tidy values from getProfileInput().
 * @return array Error messages by field name. Empty when everything is fine.
 */
function validateProfileInput(array $input): array
{
    $errors = [];

    // ---------- Full name ----------
    if ($input['name'] === '') {
        $errors['name'] = 'Please enter your full name.';
    } elseif (mb_strlen($input['name']) > 100) {
        $errors['name'] = 'Your name must be 100 characters or fewer.';
    }

    // ---------- Phone ----------
    // Allowed: digits, spaces, dashes and one + at the start. It must contain 10 to 15 digits.
    $phoneHasAllowedCharacters = (preg_match('/^\+?[0-9 \-]+$/', $input['phone']) === 1);
    $phoneDigitCount = strlen(preg_replace('/[^0-9]/', '', $input['phone']));

    if ($input['phone'] === '') {
        $errors['phone'] = 'Please enter your phone number.';
    } elseif (!$phoneHasAllowedCharacters || $phoneDigitCount < 10 || $phoneDigitCount > 15 || strlen($input['phone']) > 20) {
        $errors['phone'] = 'Please enter a valid phone number, for example 0300 1234567.';
    }

    // ---------- Delivery address (optional) ----------
    if (mb_strlen($input['address']) > 500) {
        $errors['address'] = 'Your address must be 500 characters or fewer.';
    }

    return $errors;
}

/**
 * Read the fields of the admin medicine form (FR-34).
 *
 * @return array name, sku, category, drap_category, manufacturer,
 *               description, price, reorder_threshold
 */
function getMedicineInput(): array
{
    return [
        'name' => trim(postValue('name')),
        'sku' => strtoupper(trim(postValue('sku'))),
        'category' => trim(postValue('category')),
        'drap_category' => trim(postValue('drap_category')),
        'manufacturer' => trim(postValue('manufacturer')),
        'description' => trim(postValue('description')),
        'price' => trim(postValue('price')),
        'reorder_threshold' => trim(postValue('reorder_threshold')),
    ];
}

/**
 * Check the fields of the medicine form.
 *
 * FR-34 makes name, SKU, DRAP category, manufacturer, unit price, description
 * and reorder threshold all mandatory.
 *
 * @param array    $input            The tidy values from getMedicineInput().
 * @param Medicine $medicineModel    Used to check the SKU is not already taken.
 * @param int|null $ignoreMedicineId When editing, the medicine keeps its own SKU.
 * @return array Error messages by field name. Empty when everything is fine.
 */
function validateMedicineInput(array $input, Medicine $medicineModel, ?int $ignoreMedicineId = null): array
{
    $errors = [];

    // ---------- Name ----------
    if ($input['name'] === '') {
        $errors['name'] = 'Please enter the medicine name.';
    } elseif (mb_strlen($input['name']) > 150) {
        $errors['name'] = 'The name must be 150 characters or fewer.';
    }

    // ---------- SKU ----------
    // Letters, digits and dashes only, so it stays easy to type and read.
    if ($input['sku'] === '') {
        $errors['sku'] = 'Please enter an SKU, for example PCM-500.';
    } elseif (preg_match('/^[A-Z0-9\-]{2,50}$/', $input['sku']) !== 1) {
        $errors['sku'] = 'The SKU may only contain letters, numbers and dashes.';
    } elseif ($medicineModel->skuExists($input['sku'], $ignoreMedicineId)) {
        $errors['sku'] = 'Another medicine already uses this SKU.';
    }

    // ---------- Category and DRAP category ----------
    if ($input['category'] === '') {
        $errors['category'] = 'Please enter a category, for example Antibiotic.';
    } elseif (mb_strlen($input['category']) > 100) {
        $errors['category'] = 'The category must be 100 characters or fewer.';
    }

    if ($input['drap_category'] === '') {
        $errors['drap_category'] = 'Please choose a DRAP category.';
    } elseif (mb_strlen($input['drap_category']) > 100) {
        $errors['drap_category'] = 'The DRAP category must be 100 characters or fewer.';
    }

    // ---------- Manufacturer ----------
    if ($input['manufacturer'] === '') {
        $errors['manufacturer'] = 'Please enter the manufacturer.';
    } elseif (mb_strlen($input['manufacturer']) > 150) {
        $errors['manufacturer'] = 'The manufacturer must be 150 characters or fewer.';
    }

    // ---------- Description ----------
    if ($input['description'] === '') {
        $errors['description'] = 'Please describe what this medicine is for.';
    } elseif (mb_strlen($input['description']) > 2000) {
        $errors['description'] = 'The description must be 2000 characters or fewer.';
    }

    // ---------- Price ----------
    // is_numeric() accepts "45" and "45.50" but not "abc" or an empty box.
    if ($input['price'] === '') {
        $errors['price'] = 'Please enter the unit price.';
    } elseif (!is_numeric($input['price'])) {
        $errors['price'] = 'The price must be a number, for example 45 or 45.50.';
    } elseif ((float) $input['price'] < 0) {
        $errors['price'] = 'The price cannot be negative.';
    } elseif ((float) $input['price'] > 99999999) {
        $errors['price'] = 'That price is too large.';
    }

    // ---------- Reorder threshold ----------
    if ($input['reorder_threshold'] === '') {
        $errors['reorder_threshold'] = 'Please enter the low-stock threshold.';
    } elseif (!ctype_digit($input['reorder_threshold'])) {
        $errors['reorder_threshold'] = 'The threshold must be a whole number, for example 20.';
    } elseif ((int) $input['reorder_threshold'] > 100000) {
        $errors['reorder_threshold'] = 'That threshold is too large.';
    }

    return $errors;
}

/**
 * Read the fields of the admin batch form (FR-35).
 *
 * @return array quantity, supplier_name, cost_price, received_date, expiry_date
 */
function getBatchInput(): array
{
    return [
        'quantity' => trim(postValue('quantity')),
        'supplier_name' => trim(postValue('supplier_name')),
        'cost_price' => trim(postValue('cost_price')),
        'received_date' => trim(postValue('received_date')),
        'expiry_date' => trim(postValue('expiry_date')),
    ];
}

/**
 * Check that a date is real and written as YYYY-MM-DD.
 *
 * checkdate() catches dates that look right but do not exist, such as
 * 2026-02-30 or 2026-13-01.
 *
 * @param string $date The date to check.
 * @return bool True when the date is a real calendar date.
 */
function isValidDate(string $date): bool
{
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
        return false;
    }

    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

/**
 * Check the fields of the batch form.
 *
 * FR-35: a batch needs a quantity, supplier name, cost price, received date
 *        and expiry date.
 * FR-36: "The system shall prevent addition of a batch with an expiry date in
 *        the past."
 *
 * @param array $input       The tidy values from getBatchInput().
 * @param int   $minQuantity The lowest quantity allowed. When editing a batch,
 *                           this is how many units orders already hold.
 * @return array Error messages by field name. Empty when everything is fine.
 */
function validateBatchInput(array $input, int $minQuantity = 1): array
{
    $errors = [];

    // ---------- Quantity ----------
    if ($input['quantity'] === '') {
        $errors['quantity'] = 'Please enter how many units arrived.';
    } elseif (!ctype_digit($input['quantity'])) {
        $errors['quantity'] = 'The quantity must be a whole number.';
    } elseif ((int) $input['quantity'] < $minQuantity) {
        if ($minQuantity > 1) {
            $errors['quantity'] = 'This batch cannot go below ' . $minQuantity
                . ', because that many units are already promised to orders.';
        } else {
            $errors['quantity'] = 'The quantity must be at least 1.';
        }
    } elseif ((int) $input['quantity'] > 1000000) {
        $errors['quantity'] = 'That quantity is too large.';
    }

    // ---------- Supplier ----------
    if ($input['supplier_name'] === '') {
        $errors['supplier_name'] = 'Please enter the supplier name.';
    } elseif (mb_strlen($input['supplier_name']) > 150) {
        $errors['supplier_name'] = 'The supplier name must be 150 characters or fewer.';
    }

    // ---------- Cost price ----------
    if ($input['cost_price'] === '') {
        $errors['cost_price'] = 'Please enter what the pharmacy paid per unit.';
    } elseif (!is_numeric($input['cost_price'])) {
        $errors['cost_price'] = 'The cost price must be a number, for example 27.50.';
    } elseif ((float) $input['cost_price'] < 0) {
        $errors['cost_price'] = 'The cost price cannot be negative.';
    } elseif ((float) $input['cost_price'] > 99999999) {
        $errors['cost_price'] = 'That cost price is too large.';
    }

    // ---------- Received date ----------
    if ($input['received_date'] === '') {
        $errors['received_date'] = 'Please enter the date this batch arrived.';
    } elseif (!isValidDate($input['received_date'])) {
        $errors['received_date'] = 'Please enter a real date.';
    } elseif (strtotime($input['received_date']) > strtotime(date('Y-m-d'))) {
        $errors['received_date'] = 'The received date cannot be in the future.';
    }


    // ---------- Expiry date (FR-36) ----------
    if ($input['expiry_date'] === '') {
        $errors['expiry_date'] = 'Please enter the expiry date.';
    } elseif (!isValidDate($input['expiry_date'])) {
        $errors['expiry_date'] = 'Please enter a real date.';
    } elseif (strtotime($input['expiry_date']) <= strtotime(date('Y-m-d'))) {
        // FR-36: expired stock must never enter the system.
        $errors['expiry_date'] = 'The expiry date must be in the future. Expired stock cannot be added.';
    } elseif (
        !isset($errors['received_date'])
        && $input['received_date'] !== ''
        && strtotime($input['expiry_date']) <= strtotime($input['received_date'])
    ) {
        $errors['expiry_date'] = 'The expiry date must be after the date the batch arrived.';
    }

    return $errors;
}