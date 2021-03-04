<?php

namespace main\app\ctrl\admin;

use main\app\classes\PermissionGlobal;
use main\app\classes\SettingsLogic;
use main\app\classes\UserLogic;
use main\app\ctrl\BaseAdminCtrl;
use main\app\event\CommonPlacedEvent;
use main\app\event\Events;
use main\app\model\agile\SprintModel;
use main\app\model\issue\IssueModel;
use main\app\model\project\ProjectCatalogLabelModel;
use main\app\model\project\ProjectModel;
use main\app\model\ActivityModel;
use main\app\model\project\ProjectLabelModel;
use main\app\model\project\ProjectRoleModel;
use main\app\model\project\ProjectRoleRelationModel;
use main\app\model\project\ProjectUserRoleModel;
use main\app\model\project\ProjectVersionModel;
use main\app\model\project\ProjectModuleModel;
use main\app\classes\ProjectLogic;
use main\app\classes\UserAuth;
use main\app\classes\PermissionLogic;
use main\app\model\user\UserModel;
use main\app\service\ProjectService;

/**
 * 后台的项目管理模块
 */
class ProjectRoles extends BaseAdminCtrl
{
    public static $page_sizes = [10, 20, 50, 100];

    /**
     * 后台的项目管理的构造函数
     * Project constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $userId = UserAuth::getId();
        $this->addGVar('top_menu_active', 'system');
        $check = PermissionGlobal::check($userId, PermissionGlobal::MANAGER_PROJECT_PERM_ID);

        if (!$check) {
            $this->error('权限错误', '您还未获取此模块的权限！');
            exit;
        }
    }

    public function pageIndex()
    {
        $data = [];
        $data['title'] = '项目角色';
        $data['nav_links_active'] = 'user';
        $data['left_nav_active'] = 'project_roles';
        $model = new ProjectModel();
        $projectsArr = $model->getRows('id,name',['archived'=>'N']);

        $userModel = new UserModel();
        $usersArr = $userModel->getAll(false);
        $usersKeyArr = array_column($usersArr, null, 'uid');

        $projectRoleModel = new ProjectRoleModel();
        $projectRolesArr =  $projectRoleModel->getsAll(false);

        $projectUserRoleModel = new ProjectUserRoleModel();
        $projectUserRoleArr = $projectUserRoleModel->getAll(false);
        $projectUserRoleKeyArr = [];
        foreach ($projectUserRoleArr as $item) {
            $projectUserRoleKeyArr[$item['project_id'].'@'.$item['user_id']] = $item;
        }
        foreach ($projectsArr as &$project) {
            $rolesArr = [];
            foreach ($projectRolesArr as $k=>$projectRoleArr) {
                if($projectRoleArr['project_id']==$project['id']){
                    $rolesArr[] = $projectRoleArr;
                    unset($projectRolesArr[$k]);
                }
            }
            if(!empty($rolesArr)){
                foreach ($rolesArr as &$role) {
                    $userArr = [];
                    foreach ($projectUserRoleArr as $projectUserRole) {
                        if($projectUserRole['role_id']==$role['id']){
                            if(isset($usersKeyArr[$projectUserRole['user_id']])){
                                $userArr[] = $usersKeyArr[$projectUserRole['user_id']];
                            }
                        }
                    }
                    $role['users'] = $userArr;
                }
            }
            $project['roles'] = $rolesArr;
        }
        $data['project_roles'] = $projectsArr;
        $userModel = new UserModel();
        $users = $userModel->getAll(false);
        foreach ($users as &$user) {
            $user = UserLogic::format($user);
        }
        $data['users'] = $users;
        // print_r($projectsArr);die;
        $this->render('twig/admin/project/project_roles.twig', $data);
    }


    /**
     * @throws \Exception
     */
    public function fetchRoleUser()
    {
        $roleId = null;
        if (isset($_GET['_target'][3])) {
            $roleId = (int)$_GET['_target'][3];
        }
        if (isset($_GET['role_id'])) {
            $roleId = (int)$_GET['role_id'];
        }
        if (!$roleId) {
            $this->ajaxFailed('参数错误', 'role_id不能为空');
        }
        $roleId = intval($roleId);

        // @todo 判断是否拥有权限
        $userId = UserAuth::getId();
        $model = new ProjectRoleModel();
        $role = $model->getById($roleId);
        if (!PermissionLogic::check($role['project_id'], $userId, PermissionLogic::ADMINISTER_PROJECTS)) {
            //$this->ajaxFailed(' 权限受限 ', '您没有权限执行此操作');
        }
        $userModel = new UserModel();
        $users = $userModel->getAll(false);
        foreach ($users as &$user) {
            $user = UserLogic::format($user);
        }
        $data['users'] = $users;
        $usersKeyArr = array_column($users, null, 'uid');
        $model = new ProjectUserRoleModel();
        $rolesArr = $model->getsRoleId($roleId);
        foreach ($rolesArr as $k =>$role) {
            if(!isset($usersKeyArr[$role['user_id']])){
                unset($rolesArr[$k]);
            }
        }
        sort($rolesArr);
        $data['role_users'] = $rolesArr;
        unset($model, $userModel);
        $this->ajaxSuccess('ok', $data);
    }

    /**
     * 获取关联用户的项目数据
     * @throws \Exception
     */
    public function gets()
    {

        $this->ajaxSuccess('ok', []);
    }


}
