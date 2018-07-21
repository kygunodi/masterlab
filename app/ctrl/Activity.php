<?php

namespace main\app\ctrl;

use main\app\classes\ActivityLogic;
use main\app\classes\OrgLogic;
use main\app\classes\ProjectLogic;
use main\app\classes\UserAuth;
use main\app\model\issue\IssueFileAttachmentModel;
use main\app\model\OrgModel;
use main\app\model\project\ProjectModel;

class Activity extends BaseUserCtrl
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * detail
     */
    public function fetchByUser()
    {
        $userId = UserAuth::getId();
        $page = 1;
        if (isset($_GET['page'])) {
            $data['page'] = $page = (int)$_GET['page'];
        }
        list($data['activity_list'], $data['count']) = ActivityLogic::filterByUser($userId, $page);

        $this->ajaxSuccess('ok', $data);
    }

    public function fetchByProject()
    {
        if (isset($_GET['_target'][2])) {
            $id = (int)$_GET['_target'][2];
        }
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }

        $model = new OrgModel();
        $org = $model->getById($id);
        if (empty($org)) {
            $this->ajaxFailed('failed,server_error');
        }

        $model = new ProjectModel();
        $projects = $model->getsByOrigin($id);

        $data = [];
        $data['projects'] = $projects;

        $this->ajaxSuccess('success', $data);
    }

}
