<?php
class HomeController {
  public function index(){
    if (is_logged_in()) {
      $role = user()['role'] ?? '';
      if ($role === 'admin') { redirect('admin/sales'); }
      if ($role === 'agent') { redirect('agent/dashboard'); }
    }
    redirect('login'); // adjust if your login route differs
  }
}
