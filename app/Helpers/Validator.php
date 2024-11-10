<?php

class Validator
{
  public static $validation_errors = [], $validated_data = [];

  public static function setRules(array $data, array $rules): object
  {

    foreach ($rules as $field => $ruleset) {
      $value = $data[$field] ?? null;

      foreach ($ruleset as $rule) {
        if (!isset(self::$validation_errors[$field])) {
          $validated_value = self::validate($value, $rule, $field, $data);
        }
      }

      self::$validated_data[$field] = $validated_value ?? null;
    }

    return new Validator();
  }

  public static function validate($value, string $rule, string $field, array $data)
  {
    if ($rule === "required" && empty($value)) {
      self::setValidationError($field, $field . " field is required.");
    }

    if (!empty($value)) {
      if ($rule === "alpha" && !preg_match("/^[a-zA-Z\s'-]+$/", $value)) {
        self::setValidationError($field, $field . " input may only contain letters, spaces, apostrof (') and hyphens (-).");
      }

      if ($rule === "alpha_num") {
        if (!preg_match("/^[a-zA-Z0-9._-]+$/", $value)) {
          self::setValidationError($field, $field . " input may only contain letters, numbers, periods (.), underscores (_), and hyphens (-).");
        }
      }

      if ($rule === "alpha_num_space") {
        if (!preg_match("/^[a-zA-Z0-9. _-]+$/", $value)) {
          self::setValidationError($field, $field . " input may only contain letters, spaces, numbers, periods (.), underscores (_), and hyphens (-).");
        }
      }

      if ($rule === "num" && !is_numeric($value)) {
        self::setValidationError($field, $field . " input must be numeric");
      }

      if ($rule === "lowercase" && $value !== strtolower($value)) {
        self::setValidationError($field, $field . " input letters must be lowercase.");
      }

      if (strpos($rule, "min_length") !== false && strpos($rule, ":") !== false) {
        $rule = explode(":", $rule)[1];

        if (strlen($value) < (int)$rule) {
          self::setValidationError($field, $field . " input must be at least {$rule} characters long.");
        }
      }

      if (strpos($rule, "max_length") !== false && strpos($rule, ":") !== false) {
        $rule = explode(":", $rule)[1];

        if (strlen($value) > (int)$rule) {
          self::setValidationError($field, $field . " input must not exceed {$rule} characters.");
        }
      }

      if ($rule === "numeric" && !is_numeric($value)) {
        self::setValidationError($field, $field . " input must be numeric.");
      }

      if ($rule === "email") {
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          self::setValidationError($field, $field . " input must be a valid email address.");
        }
      }

      if (strpos($rule, "match") !== false && strpos($rule, ":") !== false) {
        $match = explode(":", $rule);
        if ($value !== $data[$match[1]]) {
          self::setValidationError($field, $field . " doesn't match.");
        }
      }

      if ($rule === "phone_number" && !preg_match("/^08[0-9]{10,12}$/", $value)) {
        self::setValidationError($field, $field . " input must be a valid phone number.");
      }

      if ($rule === "date") {
        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
          self::setValidationError($field, $field . " input format must be Y-m-d.");
          return;
        }

        $parts = explode("-", $value);
        if (!checkdate($parts[1], $parts[2], $parts[0])) {
          self::setValidationError($field, $field . " input must be a valid date.");
        }
      }
    }

    return $value;
  }

  public static function setValidationError($field, $message): void
  {
    self::$validation_errors[$field] = $message;
  }

  public function hasValidationErrors(): bool
  {
    return !empty(self::$validation_errors);
  }

  public static function hasValidationError($field): bool
  {
    return isset(self::$validation_errors[$field]);
  }

  public function getValidationErrors(): array
  {
    $validation_errors = self::$validation_errors ?? "";
    self::$validation_errors = [];

    return $validation_errors;
  }

  public function validated(): array
  {
    return empty(self::$validation_errors) ? self::$validated_data : [];
  }
}
