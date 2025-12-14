<?php
class Auth {
  public static function attempt($pdo, $email, $password){
    $st = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u) {
      // Block login for inactive users (default to active when column missing)
      if (($u['status'] ?? 'active') !== 'active') {
        return false;
      }
      $stored = (string)($u['password'] ?? '');
      // Plain-text only comparison (UAT-only)
      if (hash_equals($stored, $password)) {
        $_SESSION['user'] = [
          'id'    => $u['id'],
          'name'  => $u['name'],
          'email' => $u['email'],
          'role'  => $u['role'],
        ];
        return true;
      }
    }
    return false;
  }

  public static function logout(){ unset($_SESSION['user']); }
}
