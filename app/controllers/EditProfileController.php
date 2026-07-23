<?php
namespace app\controllers;

require_once __DIR__ . '/../models/EditProfileModel.php';

use app\models\EditProfileModel;

class EditProfileController {

    private EditProfileModel $edit_profile;

    function __construct($pdo) {
        $this->edit_profile = new EditProfileModel($pdo);
    }

    /* ── aggiornamento profilo completo ─────────────────────────── */

    public function updateProfile(
        string $oldUsername,
        string $newUsername,
        string $email,
        string $bio,
        string $oldPassword,
        string $newPassword,
        string $country,
        ?array $avatarFile   = null,  // $_FILES['avatar'] oppure null
        bool   $removeAvatar = false  // true = l'utente ha cliccato "Rimuovi Avatar"
    ): string {

        /* rimozione esplicita ha priorità sull'upload */
        if ($removeAvatar) {
            $avatarPath = 'REMOVE';
        } else {
            $avatarPath = $this->edit_profile->handleAvatarUpload($avatarFile, $oldUsername);
            if ($avatarPath === 'erroreAvatar') return 'erroreAvatar';
        }

        return $this->edit_profile->updateProfile(
            $oldUsername,
            $newUsername,
            $email,
            $bio,
            $oldPassword,
            $newPassword,
            $country,
            $avatarPath
        );
    }

    /* ── rendering view ─────────────────────────────────────────── */

    public function printEditProfile(string $username): string
    {
        require_once 'app/services/countries.php';
        $profile = $this->edit_profile->getProfile($username);

        ob_start();
        require_once 'app/views/editProfile.php';
        return ob_get_clean();
    }
}