<?php
namespace App\Controller;

session_start();

use App\Core\View;
use App\Core\Theme;
use App\Core\ErrorManager as Error;

class Admin
{
    public function dashboard()
    {
        $view = new View("back/dashboard", "back");
        $view->assign("activePage", "dashboard");

    }

    public function user()
    {
        $view = new View("back/user", "back");
        $view->assign("activePage", "user");
    }

    public function role()
    {
        $view = new View("back/role", "back");
        $view->assign("activePage", "role");
    }

    public function pageList()
    {
        $view = new View("back/pageList", "back");
        $view->assign("activePage", "page");
    }

    public function pageTemplate()
    {
        $view = new View("back/pageTemplate", "back");
        $view->assign("activePage", "page");
    }

    public function comment()
    {
        $view = new View("back/comment", "back");
        $view->assign("activePage", "comment");
    }

    public function visualSetting() {
        $selected = htmlspecialchars($_POST['selectThemeName'] ?? "default");
        $view = new View("back/visualSetting", "back");
        $theme = new Theme();
        $errorAndTheme = ['error'=>"", 'theme'=>$selected];
        $path = preg_replace("%^.*(/Assets/themes/)$%", "$1", PATH);

        if (!empty($_POST['submitTheme']))
            $errorAndTheme = $this->createTheme();
        else if (!empty($_POST['modify']))
            $errorAndTheme = $this->modifyTheme();
        else if (!empty($_POST['delete']))
            $errorAndTheme = $this->deleteTheme();
        else if (!empty($_POST['import']))
            $errorAndTheme = $this->importTheme();
        else if (!empty($_POST['export']))
            $errorAndTheme = $this->exportTheme();
        else if (!empty($_POST['rename']))
            $errorAndTheme = $this->renameTheme();

        $selectedTheme = $errorAndTheme['theme'];
        $error = $errorAndTheme['error'];
        $theme->getByName($selectedTheme);

        $view->assign("content", json_decode($theme->getContent(), true));
        $view->assign("selectedTheme", $selectedTheme);
        $view->assign("activePage", "visualSetting");
        $view->assign("themeList", Theme::getThemeList());
        $view->assign("error", $error);
    }

    /**
     * create a theme
     * @return array
     */
    private function createTheme() {
        $theme = new Theme();

        if (empty($_POST['themeName']))
            return ['error'=>"You need to en enter a name", 'theme'=>"default"];

        $themeName = htmlspecialchars($_POST['themeName']);
        unset($_POST['themeName']);
        unset($_POST['submitTheme']);
        unset($_POST['selectThemeName']);
        unset($_POST['picture']);

        if (Theme::exist($themeName))
            return ['error'=>"Theme name already exist", 'theme'=>"default"];

        $theme->setName($themeName);
        $theme->setContent(json_encode($_POST));
        $theme->save();

        return ['error'=>"", 'theme'=>$themeName];
    }

    /**
     * modify a theme
     * @return array
     */
    private function modifyTheme() {
        $theme = new Theme();

        if (empty($_POST['selectThemeName']))
            return ['error'=>"You need to en enter a name", 'theme'=>"default"];

        $themeName = htmlspecialchars($_POST['selectThemeName']);
        unset($_POST['themeName']);
        unset($_POST['modify']);
        unset($_POST['submitTheme']);
        unset($_POST['selectThemeName']);
        unset($_POST['picture']);

        if (!Theme::exist($themeName))
            return ['error'=>"Theme name does not exist", 'theme'=>"default"];

        $theme->setName($themeName);
        $theme->setContent(json_encode($_POST));
        $theme->save();

        return ['error'=>"", 'theme'=>$themeName];
    }

    /**
     * delete a theme
     * @return array
     */
    private function deleteTheme() {
        $theme = new Theme();

        if (empty($_POST['selectThemeName']))
            return ['error'=>"You need to en enter a name", 'theme'=>"default"];

        $themeName = htmlspecialchars($_POST['selectThemeName']);

        if ($themeName == "default")
            return ['error'=>"You can't delete default theme", 'theme'=>"default"];

        if (!Theme::exist($themeName))
            return ['error'=>"Theme name does not exist", 'theme'=>"default"];

        Theme::delete($themeName);

        return ['error'=>"", 'theme'=>"default"];
    }

    /**
     * import a theme
     * @return array
     */
    private function importTheme() {
        $theme = new Theme();
        $name = basename($_FILES['fileTheme']['name']);
        $nameWithoutExt = explode('.', $name)[0];
        $tmpName = $_FILES['fileTheme']['tmp_name'];
        $size = $_FILES['fileTheme']['size'];
        $type = $_FILES['fileTheme']['type'];

        if ($size <= 0 || $size > 20000)
            return ['error'=>"File size too heavy", 'theme'=>"default"];

        if ($type !== "application/json")
            return ['error'=>"File must be a json", 'theme'=>"default"];

        if (Theme::exist($nameWithoutExt))
            return ['error'=>"Theme name already exist", 'theme'=>"default"];

        $theme->setName($nameWithoutExt);
        $theme->setContent(file_get_contents($tmpName));
        $theme->save();

        return ['error'=>"", 'theme'=>$nameWithoutExt];
    }

    /**
     * export a theme
     * @return array
     */
    public function exportTheme() {
        $theme = new Theme();
        $selectedTheme = htmlspecialchars($_POST['selectThemeName']);

        if (empty($selectedTheme))
            return ['error'=>"You need to en enter a name", 'theme'=>"default"];

        $fullPath = PATHTMP."/$selectedTheme.zip";

        if (!Theme::exist($selectedTheme))
            return ['error'=>"File does not exist", 'theme'=>"default"];

        $theme->getByName($selectedTheme);
        $code = $theme->compressToZip($selectedTheme.".zip");

        if ($code !== 0)
            return ['error'=>"Something wrong with compression : $code", 'theme'=>$selectedTheme];

        header('Content-Description: Download theme');
        /* header('Content-Type: application/octet-stream'); */
        header('Content-Disposition: attachment; filename="'.$selectedTheme.'.zip"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);

        return ['error'=>"", 'theme'=>$selectedTheme];
    }

    /**
     * rename a theme
     * @return array
     */
    public function renameTheme() {
        $theme = new Theme();
        $selectedTheme = htmlspecialchars($_POST['selectThemeName']);
        $renameTheme = htmlspecialchars($_POST['renameTheme']);

        if (empty($renameTheme) || empty($selectedTheme))
            return ['error'=>"You need to en enter a name", 'theme'=>"default"];

        if ($selectedTheme === $renameTheme)
            return ['error'=>"New name and old name cannot be the same", 'theme'=>$selectedTheme];

        if (!Theme::exist($selectedTheme))
            return ['error'=>"File does not exist", 'theme'=>"default"];

        $theme->getByName($selectedTheme);
        $theme->setName($renameTheme);
        $theme->save();
        Theme::delete($selectedTheme);

        return ['error'=>"", 'theme'=>$renameTheme];
    }

    public function plugin()
    {
        $view = new View("back/plugin", "back");
        $view->assign("activePage", "plugin");
    }

    public function globalSetting()
    {
        $view = new View("back/globalSetting", "back");
        $view->assign("activePage", "globalSetting");
    }
}
