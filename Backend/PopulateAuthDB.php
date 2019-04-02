<?php
$config                    = parse_ini_file('AuthDB.ini');
$csv = array_map('str_getcsv', file($argv[1]));
$project_name=@$argv[2];
if (@$argv[2]==null) {
	echo "You must specify project name in second arg. ex: php PopulateAuthDB.php test.csv projectname\n";
	exit;
}
unset($csv[0]);
try {
$dbh = new PDO('mysql:host='.$config['host'].';dbname='.$config['database'].'', $config['username'], $config['password']);
} catch (PDOException $e) {
	    print "Error !: " . $e->getMessage() ."\n" ;
	    die();
}

$stmt = $dbh->prepare("SELECT id from Projects where name=:name");
$stmt->execute(['name' => $project_name]); 
$project = $stmt->fetch();
if ($project==null) {
echo "Project name not found in bdd \n ";
	exit();
}



$stmt = $dbh->prepare("INSERT INTO users (name, firstname,mail,mdp,status,mail_validation,type,created_at,updated_at) VALUES (:name, :firstname,:mail,:mdp,:status,:mail_validation,:type,:created_at,:updated_at)");
foreach ($csv as $key => $value) {
	$stmt->bindParam(':name', $value[1]);
	$stmt->bindParam(':firstname', $value[2]);
	$stmt->bindParam(':mail', $value[0]);
	$mdp=bin2hex(openssl_random_pseudo_bytes(16));
	$mdp=password_hash($mdp, PASSWORD_DEFAULT);
	$stmt->bindParam(':mdp', $mdp);
	$status="1";
	$stmt->bindParam(':status', $status);
	$mail_validation="1";
	$stmt->bindParam(':mail_validation', $mail_validation);
	$type="0";
	$stmt->bindParam(':type', $type);
	$date=date('Y-m-d');
	$stmt->bindParam(':created_at', $date);
	$stmt->bindParam(':updated_at', $date);
	if ($value[1]!="" and $value[2]!="") {
		$stmt->execute();
	}
}
echo "Users created successfully! \n";


if ($project!=false) {

	$id=$project['id'];


foreach ($csv as $key => $value) {
		$stmt = $dbh->prepare("SELECT id_user from users where mail=:mail");
		$stmt->execute(['mail' => $value[0]]); 
		$user = $stmt->fetch();

		$stmt = $dbh->prepare("SELECT id_user from Projects_access_right where id_user=:id_user and id_project=:id_project");
		$stmt->execute(['id_user' => $user['id_user'],'id_project'=>$id]); 
		$check = $stmt->fetch();
		if ($check!=null) {
			echo "User ".$value[0]." already have access to ".$project_name."\n" ;
		}else{
		$stmt = $dbh->prepare("INSERT INTO Projects_access_right (id_project, id_user,user_type,created_at,updated_at) VALUES (:id_project, :id_user,:user_type,:created_at,:updated_at)");
		$stmt->bindParam(':id_project', $id);
		$stmt->bindParam(':id_user', $user['id_user']);
		$type="0";
		$stmt->bindParam(':user_type', $type);
		$date=date('Y-m-d');
		$stmt->bindParam(':created_at', $date);
		$stmt->bindParam(':updated_at', $date);
		if ($value[1]!="" and $value[2]!="") {
			$stmt->execute();
			echo "Creating access right for ".$value[0]."\n";
		}

		}



	}


}




?>