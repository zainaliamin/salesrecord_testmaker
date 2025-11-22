<?php
class AdminUserController {
  public function index(){
    require_role('admin'); global $pdo;
    $users = $pdo->query("SELECT id,name,email,password,role,created_at FROM users ORDER BY role ASC, id ASC")->fetchAll();
    require __DIR__.'/../../views/admin/users/index.php';
  }

  public function create(){
    require_role('admin');
    require __DIR__.'/../../views/admin/users/create.php';
  }

  public function store(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if ($name==='' || $email==='' || $pass1==='' || $pass2==='') exit('All fields are required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit('Invalid email');
    if ($pass1 !== $pass2) exit('Passwords do not match');
    if (strlen($pass1) < 6) exit('Password must be at least 6 characters');

    $st = $pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $st->execute([$email]);
    if ($st->fetch()) exit('Email already exists');

    // UAT ONLY: store plain text in the 'password' column
    $plain = $pass1;

    $st = $pdo->prepare("INSERT INTO users(name,email,password,role,created_at) VALUES(?,?,?,'agent',NOW())");
    $st->execute([$name,$email,$plain]);

    redirect('admin/users');
  }

  public function passwordForm(){
    require_role('admin');
    require __DIR__.'/../../views/admin/password.php';
  }

  public function passwordUpdate(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $current = $_POST['current_password'] ?? '';
    $pass1   = $_POST['new_password'] ?? '';
    $pass2   = $_POST['new_password_confirm'] ?? '';

    if ($current==='' || $pass1==='' || $pass2==='') exit('All fields are required');
    if ($pass1 !== $pass2) exit('New passwords do not match');
    if (strlen($pass1) < 6) exit('New password must be at least 6 characters');

    $st = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
    $st->execute([ user()['id'] ]);
    $u = $st->fetch();
    if (!$u) exit('User not found');

    // UAT ONLY: verify current password as plain text
    if (!hash_equals((string)$u['password'], (string)$current)) {
      exit('Current password is incorrect');
    }

    // UAT ONLY: save new password as plain text
    $newPlain = $pass1;
    $st = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $st->execute([$newPlain, user()['id']]);

    Auth::logout();
    redirect('login');
  }
}
