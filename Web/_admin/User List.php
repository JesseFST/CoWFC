<?php
include($_SERVER["DOCUMENT_ROOT"] . '/_site/AdminPage.php');

final class UserList extends AdminPage {
	private $users = array();
	private $banned_list = array();
	
	private function handleReq(): void {
		if(isset($_POST['action'], $_POST['identifier'])){
			switch($_POST['action']){
				case 'ban': if(isset($_POST['reason'])){ $this->site->database->banIP($_POST['identifier'], $_POST['reason'], 60 * (int)$_POST['time']); } break;
				case 'unban': $this->site->database->unbanIP($_POST['identifier']);break;
			}
		}
		$this->users = $this->site->database->getUsers();
		$this->banned_list = $this->site->database->getBannedList();
	}
	
	private function calcFC(int $profile_id, string $game_id='RMCJ'): string {
		$csum = md5(pack('V',$profile_id).strrev($game_id),true);
		$out = $profile_id | ( ord($csum) & 0xfe ) << 31;
		return str_pad($out, 12, '0', STR_PAD_LEFT);
	}
	
	private function buildBlacklistTable(): void {
		echo '<table class="table table-striped table-bordered table-hover dataTable no-footer dtr-inline" style="width: 100%;">';
		echo '<thead><tr>';
		echo "<th class='sorting-asc'>Name</th><th>Action</th><th>gameid</th><th>E</th><th>pid</th><th>gsbrcd</th><th>userid</th><th>IP Address</th><th>Console MAC</th><th>Friend Code</th><th>Wii Friend Code</th><th>Console Serial Number (Wii ONLY)</th>";
		echo '</tr></thead>';
		foreach($this->users as $row){
			$nasdata = json_decode($row[2], true);
			$is_console = $row[4];
			if(array_key_exists('ingamesn', $nasdata)){
				$ingamesn = $nasdata['ingamesn'];
			} elseif(array_key_exists('devname', $nasdata)){
				$ingamesn = $nasdata['devname'];
			}
			if(isset($ingamesn)){
				$ingamesn = base64_decode($ingamesn);
				if($is_console){
					$ingamesn = @iconv('UTF-16BE', 'UTF-8', $ingamesn);
				} else {
					$ingamesn = @iconv('UTF-16LE', 'UTF-8', $ingamesn);
				}
			}
			echo "<tr>";
			echo "<td>".htmlentities($ingamesn)."</td>";
			echo "<td>";
			if(in_array($nasdata['ipaddr'], $this->banned_list)){
				echo "<form action='' method='post'><input type='hidden' name='action' id='action' value='unban'><input type='hidden' name='identifier' id='identifier' value='{$nasdata['ipaddr']}'><input type='submit' class='btn btn-primary' value='Unban'></form>";
			} else {
				echo "<form action='' method='post'><input type='hidden' name='action' id='action' value='ban'><input type='hidden' name='identifier' id='identifier' value='{$nasdata['ipaddr']}'><input type='text' class='form-control' placeholder='Reason' name='reason' id='reason' style='width: 100px;'><input type='text' class='form-control' placeholder='# minutes' name='time' id='time' style='width: 100px;' value='0' maxlength='11'><input type='submit' class='btn btn-primary' value='Ban'></form>";
			}
			echo "</td>";
			echo "<td>{$row[3]}</td>";
			echo "<td>{$row[1]}</td>";
			echo "<td>{$row[0]}</td>";
			echo "<td>{$nasdata['gsbrcd']}</td>";
			echo "<td>{$row[5]}</td>";
			echo "<td>{$nasdata['ipaddr']}</td>";
			echo "<td>{$nasdata['macadr']}</td>";
			echo "<td>".substr(chunk_split($this->calcFC((int)$row[0], $row[3]),4,'-'),0,-1)."</td>";
			if(isset($nasdata['cfc']) && isset($nasdata['csnum'])){ // Wii only
				echo "<td>".substr(chunk_split($nasdata['cfc'], 4, '-'),0,-1)."</td>";
				echo "<td>{$nasdata['csnum']}</td>";
			} else {
				echo "<td>N/A</td>";
				echo "<td>N/A</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	
	protected function buildAdminPage(): void {
		$this->handleReq();
?>
<div class="content-wrapper py-3">

      <div class="container-fluid">

        <!-- Breadcrumbs -->
        <ol class="breadcrumb">
          <li class="breadcrumb-item active"><?php echo $this->meta_title; ?></li>
        </ol>

        <?php $this->buildBlacklistTable(); ?>

      </div>
      <!-- /.container-fluid -->

    </div>
    <!-- /.content-wrapper -->
<?php
	}
}
?>
