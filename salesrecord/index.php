<?php
// C:\xampp\htdocs\salesrecord\index.php

require __DIR__.'/app/init.php';
require __DIR__.'/app/Router.php';
require __DIR__.'/app/Auth.php';

// ---- Controllers (make sure files exist at these exact paths) ----
require __DIR__.'/app/Controllers/HomeController.php';           // NEW for root redirect
require __DIR__.'/app/Controllers/AuthController.php';
require __DIR__.'/app/Controllers/AgentController.php';
require __DIR__.'/app/Controllers/AdminController.php';
require __DIR__.'/app/Controllers/AdminUserController.php';      // if you actually have this
require __DIR__.'/app/Controllers/AdminBoardController.php';
require __DIR__.'/app/Controllers/AdminExpenseController.php';
require __DIR__.'/app/Controllers/ShareholderController.php';    // NEW shareholder page

$router = new Router();

/* -------------------------
   ROUTES
   ------------------------- */

// Root → send user to right place (admin/agent) or login
$router->get('',  [HomeController::class, 'index']);   // /
$router->get('/', [HomeController::class, 'index']);   // trailing slash safety

// Auth
$router->get('home',  [AuthController::class,'landing']);
$router->get('login', [AuthController::class,'showLogin']);
$router->post('login',[AuthController::class,'login']);
$router->get('logout',[AuthController::class,'logout']);

// Agent
$router->get('agent/dashboard',     [AgentController::class,'dashboard']);
$router->get('agent/sales/create',  [AgentController::class,'create']);
$router->post('agent/sales/store',  [AgentController::class,'store']);
$router->get('agent/sales/edit',    [AgentController::class,'edit']);
$router->post('agent/sales/update', [AgentController::class,'update']);
$router->get('agent/due/edit',      [AgentController::class,'editDuePayment']);
$router->post('agent/due/update',   [AgentController::class,'updateDuePayment']);
$router->get('agent/sales/lookup',  [AgentController::class,'lookupSchool']); // JSON
$router->post('agent/sales/paydue', [AgentController::class,'payDue']);

// Admin (sales)
$router->get('admin/sales',          [AdminController::class,'index']);
$router->get('admin/sales/show',     [AdminController::class,'show']);        // ?id=xx
$router->post('admin/sales/approve', [AdminController::class,'approve']);     // id + csrf
$router->post('admin/sales/reject',  [AdminController::class,'reject']);      // id + csrf + note
$router->get('admin/sales/edit',     [AdminController::class,'edit']);        // form
$router->post('admin/sales/update',  [AdminController::class,'update']);      // save
$router->get('admin/payments',       [AdminController::class,'payments']);

// Admin (expenses)
$router->get('admin/expenses',        [AdminExpenseController::class,'index']);   // list + form
$router->post('admin/expenses/store', [AdminExpenseController::class,'store']);   // add expense
$router->get('admin/expenses/edit',   [AdminExpenseController::class,'edit']);
$router->post('admin/expenses/update',[AdminExpenseController::class,'update']);
$router->post('admin/expenses/delete',[AdminExpenseController::class,'delete']);

// Admin (users) — only if controller actually exists
$router->get('admin/users',          [AdminUserController::class,'index']);
$router->get('admin/users/create',   [AdminUserController::class,'create']);
$router->post('admin/users/store',   [AdminUserController::class,'store']);
$router->get('admin/users/status',   [AdminUserController::class,'updateStatus']); // fallback if POST not routed
$router->post('admin/users/status',  [AdminUserController::class,'updateStatus']);
$router->get('admin/password',       [AdminUserController::class,'passwordForm']);
$router->post('admin/password',      [AdminUserController::class,'passwordUpdate']);

// Admin (exam boards)
$router->get('admin/boards',         [AdminBoardController::class,'index']);
$router->get('admin/boards/create',  [AdminBoardController::class,'create']);
$router->post('admin/boards/store',  [AdminBoardController::class,'store']);
$router->post('admin/boards/delete', [AdminBoardController::class,'delete']);
$router->get('admin/boards/edit',    [AdminBoardController::class,'edit']);
$router->post('admin/boards/update', [AdminBoardController::class,'update']);

// Admin (shareholder/kpi/spend)
$router->get('admin/shareholder',          [ShareholderController::class,'index']);
$router->post('admin/shareholder/spend',   [ShareholderController::class,'store']);
$router->post('admin/shareholder/update',  [ShareholderController::class,'update']);
$router->post('admin/shareholder/delete',  [ShareholderController::class,'delete']);

$router->post('admin/due-payments/approve',[AdminController::class,'approveDuePayment']);
$router->post('admin/due-payments/reject', [AdminController::class,'rejectDuePayment']);


require __DIR__.'/app/Controllers/AdminReportController.php';

$router->get('admin/reports/annual', [AdminReportController::class,'annual']);



$router->dispatch();
