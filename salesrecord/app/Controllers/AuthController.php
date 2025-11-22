<?php
class AuthController {
  public function landing(){
    if (is_logged_in()) {
      redirect(user()['role']==='admin' ? 'admin/sales' : 'agent/dashboard');
    }
    redirect('login');
  }

  public function showLogin(){ require __DIR__.'/../../views/auth/login.php'; }

  public function login(){
    global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');
    $email = clean($_POST['email'] ?? '');
    $pass  = clean($_POST['password'] ?? '');
    if (Auth::attempt($pdo, $email, $pass)) redirect('');
    $error = 'Invalid credentials';
    require __DIR__.'/../../views/auth/login.php';
  }

  public function logout(){ Auth::logout(); redirect('login'); }
}
