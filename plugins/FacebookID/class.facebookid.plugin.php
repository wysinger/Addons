<?php if (!defined('APPLICATION')) exit();

$PluginInfo['FacebookID'] = array(
   'Name' => 'Facebook ID Display',
   'Description' => 'Displays users facebook IDs in verious locations in the site.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.16'),
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
   'RegisterPermissions' => array('Plugins.FacebookID.View'),
);

class FacebookIDPlugin extends Gdn_Plugin {
   public $FacebookIDs = array();

   public function UserInfoModule_OnBasicInfo_Handler($Sender, $Args) {
      if (Gdn::Session()->CheckPermission('Plugins.FacebookID.View')) {
         // Grab the facebook ID.
         $FacebookID = Gdn::SQL()->GetWhere(
            'UserAuthentication',
            array('ProviderKey' => 'facebook', 'UserID' => $Sender->User->UserID)
         )->Value('ForeignUserKey', T('n/a'));

         echo '<dt class="Value">'.T('Facebook ID').'</dt><dd>'.$FacebookID.'</dd>';
      }
   }

   public function Base_CommentInfo_Handler($Sender, $Args) {
      if (!Gdn::Session()->CheckPermission('Plugins.FacebookID.View'))
         return;

      if (!isset($Sender->Data['Discussion']))
         return;
      
      if (!$this->FacebookIDs)
         $this->FacebookIDs = $this->GetFacebookIDs(array($Sender->Data['Discussion'], $Sender->Data['Comments']), 'InsertUserID');


      $UserID = GetValue('InsertUserID',$Sender->EventArguments['Object'],'0');
      $FacebookID = GetValue($UserID, $this->FacebookIDs, T('n/a'));
      echo '<span>'.T('Facebook ID').': '.$FacebookID.'</span>';
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param <type> $Args
    * @return <type>
    */
   public function UserController_Render_Before($Sender, $Args) {
      if (!in_array($Sender->RequestMethod, array('index', 'browse')))
         return;
      if (!Gdn::Session()->CheckPermission('Plugins.FacebookID.View'))
         return;
   }

   public function GetFacebookIDs($Datas, $UserIDColumn) {
      $UserIDs = array();
      foreach ($Datas as $Data) {
         if ($UserID = GetValue($UserIDColumn, $Data))
            $UserIDs[] = $UserID;
         else {
            $IDs = ConsolidateArrayValuesByKey($Data, $UserIDColumn);
            $UserIDs = array_merge($UserIDs, $IDs);
         }
      }

      $FbIDs = Gdn::SQL()
         ->WhereIn('UserID', array_unique($UserIDs))
         ->GetWhere(
         'UserAuthentication',
         array('ProviderKey' => 'facebook'))->ResultArray();

      $Result = ConsolidateArrayValuesByKey($FbIDs, 'UserID', 'ForeignUserKey');
      return $Result;
   }

}