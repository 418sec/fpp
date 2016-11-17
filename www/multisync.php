<html>
<head>
<?php
include 'common/menuHead.inc';
require_once("config.php");
require_once("common.php");
?>
<title><? echo $pageTitle; ?></title>
<script>
	function updateMultiSyncRemotes(checkbox) {
		var remotes = "";

		if ($('#allRemotes').is(":checked")) {
			remotes = "255.255.255.255";

			$('input.remoteCheckbox').each(function() {
				if (($(this).is(":checked")) &&
						($(this).attr("name") != "255.255.255.255"))
				{
					$(this).prop('checked', false);
					if ($(checkbox).attr("name") != "255.255.255.255")
						DialogError("WARNING", "'All Remotes' is already checked.  Uncheck 'All Remotes' if you want to select individual FPP instances.");
				}
			});
		} else {
			$('input.remoteCheckbox').each(function() {
				if ($(this).is(":checked")) {
					if (remotes != "") {
						remotes += ",";
					}
					remotes += $(this).attr("name");
				}
			});
		}

		$.get("fppjson.php?command=setSetting&key=MultiSyncRemotes&value=" + remotes
		).success(function() {
			settings['MultiSyncRemotes'] = remotes;
			if (remotes == "")
				$.jGrowl("Remote List Cleared.  You must restart fppd for the changes to take effect.");
			else
				$.jGrowl("Remote List set to: '" + remotes + "'.  You must restart fppd for the changes to take effect.");
		}).fail(function() {
			DialogError("Save Remotes", "Save Failed");
		});
	}

	function parseFPPSystems(data) {
		$('#fppSystems tbody').empty();

		var remotes = [];
		if (typeof settings['MultiSyncRemotes'] === 'string') {
			var tarr = settings['MultiSyncRemotes'].split(',');
			for (var i = 0; i < tarr.length; i++) {
				remotes[tarr[i]] = 1;
			}
		}

		if (settings['fppMode'] == 'master') {
			$('#masterLegend').show();

			var star = "<input id='allRemotes' type='checkbox' class='remoteCheckbox' name='255.255.255.255'";
			if (typeof remotes["255.255.255.255"] !== 'undefined')
				star += " checked";
			star += " onClick='updateMultiSyncRemotes(this);'>";

			var newRow = "<tr>" +
				"<td align='center'>" + star + "</td>" +
				"<td>ALL Remotes</td>" +
				"<td>255.255.255.255</td>" +
				"<td>ALL</td>" +
				"<td>Remote</td>" +
				"</tr>";
			$('#fppSystems tbody').append(newRow);
		}

		for (var i = 0; i < data.length; i++) {
			var star = "";
			var link = "";
			if (data[i].Local)
			{
				link = data[i].HostName;
				star = "*";
			} else {
				link = "<a href='http://" + data[i].IP + "/'>" + data[i].HostName + "</a>";
				if ((settings['fppMode'] == 'master') &&
						(data[i].fppMode == "remote"))
				{
					star = "<input type='checkbox' class='remoteCheckbox' name='" + data[i].IP + "'";
					if (typeof remotes[data[i].IP] !== 'undefined')
						star += " checked";
					star += " onClick='updateMultiSyncRemotes();'>";
				}
			}

			var fppMode = 'Player';
			if (data[i].fppMode == 'bridge')
				fppMode = 'Bridge';
			else if (data[i].fppMode == 'master')
				fppMode = 'Master';
			else if (data[i].fppMode == 'remote')
				fppMode = 'Remote';

			var newRow = "<tr>" +
				"<td align='center'>" + star + "</td>" +
				"<td>" + link + "</td>" +
				"<td>" + data[i].IP + "</td>" +
				"<td>" + data[i].Platform + "</td>" +
				"<td>" + fppMode + "</td>" +
				"</tr>";
			$('#fppSystems tbody').append(newRow);
		}
	}

	function getFPPSystems() {
		$('#masterLegend').hide();
		$('#fppSystems tbody').empty();
		$('#fppSystems tbody').append("<tr><td colspan=5 align='center'>Loading...</td></tr>");

		$.get("fppjson.php?command=getSetting&key=MultiSyncRemotes", function(data) {
			settings['MultiSyncRemotes'] = data.MultiSyncRemotes;
			$.get("fppjson.php?command=getFPPSystems", function(data) {
				parseFPPSystems(data);
			});
		});
	}

	function refreshFPPSystems() {
		setTimeout(function() { getFPPSystems(); }, 1000);
	}

</script>
<style>
#fppSystems{
	border: 1px;
}

.masterHeader{
	width: 15%;
}

.masterValue{
	width: 40%;
}

.masterButton{
	text-align: right;
	width: 25%;
}
</style>
</head>
<body>
<div id="bodyWrapper">
	<?php include 'menu.inc'; ?>
	<br/>
	<div id="uifppsystems" class="settings">
		<fieldset>
			<legend>Discovered FPP Systems</legend>
			<table id='fppSystems' cellspacing='5'>
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>System Name</th>
						<th>IP Address</th>
						<th>Platform</th>
						<th>Mode</th>
					</tr>
				</thead>
				<tbody>
					<tr><td colspan=5 align='center'>Loading...</td></tr>
				</tbody>
			</table>
			<hr>
			<? PrintSettingCheckbox("Send F16v2 Sync Packets", "MultiSyncCSVBroadcast", 1, 0, "1", "0"); ?> Send F16v2 Sync Packets<br>
			<? PrintSettingCheckbox("Compress FSEQ files for transfer", "CompressMultiSyncTransfers", 0, 0, "1", "0"); ?> Compress FSEQ files during copy to Remotes to speed up file sync process<br>
			<hr>
			<font size=-1>
				<span id='legend'>
				* - Local System
				<span id='masterLegend' style='display:none'><br>&#x2713; - Sync Remote FPP with this Master instance</span>
				</span>
			</font>
			<br>
			<input type='button' class='buttons' value='Refresh' onClick='getFPPSystems();'>

<?php
if ($settings['fppMode'] == 'master')
{
?>
			<input type='button' class='buttons' value='Sync Files' onClick='location.href="syncRemotes.php";'>
<?php
}
?>
		</fieldset>
	</div>
</div>
<?php include 'common/footer.inc'; ?>

<script>

$(document).ready(function() {
	getFPPSystems();
});

</script>


</body>
</html>
