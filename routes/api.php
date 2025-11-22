<?php

use App\Http\Controllers\AccesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\TypeDocumentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');


/*
|--------------------------------------------------------------------------
| Routes Protégées (Authentification requise)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ============ AUTHENTIFICATION ============
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    // ✅ ============ PROFIL PERSONNEL (pour tous les utilisateurs) ============
    Route::prefix('profile')->group(function () {
        // Récupérer son profil
        Route::get('/', [UserController::class, 'getOwnProfile']);

        // Mettre à jour UNIQUEMENT : nom_complet, email
        Route::put('/info', [UserController::class, 'updateOwnInfo']);
        Route::post('/info', [UserController::class, 'updateOwnInfo']); // Pour form-data

        // Mettre à jour UNIQUEMENT : photo_profil
        Route::post('/photo', [UserController::class, 'updateOwnPhoto']);
        Route::delete('/photo', [UserController::class, 'deleteOwnPhoto']);

        // Changer UNIQUEMENT : mot de passe
        Route::put('/password', [UserController::class, 'updateOwnPassword']);
    });

    // ============ DOCUMENTS ============
    Route::prefix('documents')->group(function () {
        // Routes accessibles à tous (avec vérification dans le controller)
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);

        // Routes nécessitant vérification d'accès
        Route::get('/{id}', [DocumentController::class, 'show'])->middleware('document.access');
        Route::post('/{id}', [DocumentController::class, 'update'])->middleware('document.access'); // Pour form-data
        Route::put('/{id}', [DocumentController::class, 'update'])->middleware('document.access');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->middleware('document.access');
        Route::get('/{id}/download', [DocumentController::class, 'download'])->middleware('document.access');
        Route::get('/{id}/versions', [DocumentController::class, 'versions']);
        Route::post('/{id}/restore', [DocumentController::class, 'restore']);

        // Permissions sur documents (Chef/Admin/Créateur)
        Route::post('/{documentId}/permissions', [AccesController::class, 'setPermissions']);
        Route::get('/{documentId}/permissions', [AccesController::class, 'getDocumentPermissions']);
        Route::delete('/{documentId}/permissions/{userId}', [AccesController::class, 'revokeAccess']);

    });

    // ============ UTILISATEURS ============
    Route::prefix('users')->group(function () {
        // Admin uniquement
        Route::middleware('admin')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::post('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });

        // Chef de département peut voir ses employés
        Route::get('/departement/employes', [UserController::class, 'employesDepartement'])
            ->middleware('chef');
    });

    // ============ DÉPARTEMENTS ============
    Route::prefix('departements')->group(function () {
        Route::get('/', [DepartementController::class, 'index']);
        Route::get('/{id}', [DepartementController::class, 'show']);

        // Stats accessible par Chef (de son département) et Admin
        Route::get('/{id}/stats', [DepartementController::class, 'stats']);

        // Admin uniquement
        Route::middleware('admin')->group(function () {
            Route::post('/', [DepartementController::class, 'store']);
            Route::put('/{id}', [DepartementController::class, 'update']);
            Route::delete('/{id}', [DepartementController::class, 'destroy']);
        });
    });

    // ============ NOTIFICATIONS ============
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // ============ ACCÈS (PERMISSIONS) ============
    Route::get('/mes-acces', [AccesController::class, 'mesAcces']);

    // ============ STATISTIQUES ============
    Route::prefix('statistiques')->group(function () {
        Route::get('/mes-stats', [StatistiqueController::class, 'mesStats']);
        Route::get('/dashboard', [StatistiqueController::class, 'dashboard']);

        // Admin uniquement
        Route::middleware('admin')->group(function () {
            Route::get('/global', [StatistiqueController::class, 'global']);
            Route::get('/rapport-hebdomadaire', [StatistiqueController::class, 'rapportHebdomadaire']);
        });
    });

    // ============ TYPES DE DOCUMENTS ============
    Route::prefix('type-documents')->group(function () {
        Route::get('/', [TypeDocumentController::class, 'index']);

        // Admin uniquement
        Route::middleware('admin')->group(function () {
            Route::post('/', [TypeDocumentController::class, 'store']);
            Route::put('/{id}', [TypeDocumentController::class, 'update']);
            Route::delete('/{id}', [TypeDocumentController::class, 'destroy']);
        });
    });

    // ============ RÔLES ============
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/test', function () {
        return response()->json(['message' => 'API en ligne 🚀']);
    });

});
