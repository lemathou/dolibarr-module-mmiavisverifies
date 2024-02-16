<?php

class MMIAvisVerifies
{

public static function send()
{
    global $db, $conf;
	
	$path_documents = DOL_DOCUMENT_ROOT.'/../documents';
    
	$fk_c_type_contact = 102;
	$fk_product_exclude = '1,2';
	$delai = $conf->global->MMIAVISVERIFIES_DELAI;
	$fk_statuses = '3';
	$p_actif = $conf->global->MMIAVISVERIFIES_FILTER_P_PRESTA_ACTIF;
	$no_p_order = $conf->global->MMIAVISVERIFIES_FILTER_O_PRESTA;
	$no_artisan = $conf->global->MMIAVISVERIFIES_FILTER_C_ARTISAN;
	
    // Commandes Dolibarr
    $sql = 'SELECT DISTINCT o.rowid, o.fk_soc, oa.fk_socpeople, o.ref, o2.p_ref, o.date_commande
        FROM '.MAIN_DB_PREFIX.'commande o
        LEFT JOIN '.MAIN_DB_PREFIX.'commande_extrafields o2
            ON o2.fk_object=o.rowid
		INNER JOIN '.MAIN_DB_PREFIX.'societe c
			ON c.rowid = o.fk_soc
		LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields c2
			ON c2.fk_object = o.fk_soc
        LEFT JOIN '.MAIN_DB_PREFIX.'element_contact oa
            ON oa.fk_c_type_contact='.$fk_c_type_contact.' AND oa.element_id=o.rowid
        WHERE (o2.netreviews_sent IS NULL OR o2.netreviews_sent="") AND (o2.netreviews_bl IS NULL OR o2.netreviews_bl = 0)
			AND o.date_valid IS NOT NULL
			'.(!empty($fk_statuses) ?' AND o.fk_statut IN ('.$fk_statuses.')' :'').'
			'.($no_artisan ?' AND (c2.artisan IS NULL OR c2.artisan=0)' :'').'
			'.($no_p_order ?' AND (o2.p_ref IS NULL OR o2.p_ref="")' :'').'
		ORDER BY o.rowid ASC';
    echo '<p>'.$sql.'</p>';
    $q = $db->query($sql);
    var_dump($q);
    if (!$q) {
        // @todo notify error
		echo '<p>Requête commandes erreur SQL !</p>';
        return false;
    }

	if (! $q->num_rows) {
		echo '<p>Liste commandes vide !</p>';
		return false;
	}
	
	$l = [];
	$oids = [];
    $p_nb = 0;
	
    // Récup commandes
    while ($o=$q->fetch_object()) {
		echo '<hr />';
        var_dump($o);
		
        // Récup socpeople livraison s'il y a
        if ($o->fk_socpeople) {
            $sql = 'SELECT a.rowid, a.email, a.firstname, a.lastname
                FROM '.MAIN_DB_PREFIX.'socpeople a
                WHERE a.rowid='.$o->fk_socpeople;
            //echo '<p>'.$sql.'</p>';
            $q2 = $db->query($sql);
            //var_dump($q2);
            $a = $q2->fetch_object();
			//var_dump($a);
        }
		else {
			$a = NULL;
		}

        // Récup societe
        $sql = 'SELECT c.rowid, c.email, c.nom, c.name_alias
            FROM '.MAIN_DB_PREFIX.'societe c
            WHERE c.rowid='.$o->fk_soc;
        //echo '<p>'.$sql.'</p>';
        $q2 = $db->query($sql);
        //var_dump($q2);
        $c = $q2->fetch_object();
		//var_dump($c);

        // Récup produits
        $sql = 'SELECT p.rowid, p.ref, p.label, p.fk_barcode_type, p.barcode, p.url, p2.p_image
            FROM '.MAIN_DB_PREFIX.'commandedet op
            INNER JOIN '.MAIN_DB_PREFIX.'product p
                ON p.rowid=op.fk_product
			LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields p2
                ON p2.fk_object=p.rowid
            WHERE op.fk_commande='.$o->rowid.'
				AND p.rowid NOT IN ('.$fk_product_exclude.')
				AND p.ref LIKE "PI-%"
				'.($p_actif ?' AND (p2.p_active=1 AND p2.p_decli_disabled IS NULL) AND p2.sync=1' :'').'
				AND p.label NOT LIKE "Remise%"
				AND p.label NOT LIKE "%livraison%"
				AND p.label NOT LIKE "%emballage%"
				AND p.label NOT LIKE "%découpe%"';
        echo '<p>'.$sql.'</p>';
        $q2 = $db->query($sql);
        var_dump($q2);
		$p_nb += $q2->num_rows;
        while ($p=$q2->fetch_object()) {
            //var_dump($p);
			
			// Marqué avis envoyé uniquement si c'est vraiment le cas !
			if (!in_array($o->rowid, $oids))
				$oids[] = $o->rowid;
			
            // Image
			$f = NULL;
			if (file_exists($img_folder=$path_documents.'/produit/'.$p->ref)) {
				$pfp = opendir($img_folder);
				//var_dump($pfp);
				while ($f=readdir($pfp)) {
					if (!in_array($f, ['.', '..'])) {
						//var_dump($f);
						break;
					}
					else {
						$f = NULL;
					}
				}
			}
			//var_dump($img_folder);
			
			$l[] = [
				'email' => ($a && $a->email ?$a->email :$c->email),
				'order_ref' => $o->ref,
				'order_date' => $o->date_commande,
				'delay' => $delai,
				'lastname' => ($a ?$a->lastname :$c->nom),
				'firstname' => ($a ?$a->firstname :$c->name_alias),
				'id_product' => $p->ref,
				'name_product' => $p->label,
				'url_product' => $p->url,
				'url_image_product' => $p->p_image,
				'gtin_ean' => $p->barcode,
				'sku' => $p->rowid,
			];
        }

    }

	if (empty($p_nb)) {
		echo '<p>Liste produits vide !</p>';
		return false;
	}
	
	echo '<hr />';
	//var_dump($l);

    $csv = 'email;order_ref;order_date;delay;lastname;firstname;id_product;name_product;url_product;url_image_product;gtin_ean;sku'."\r\n";
	foreach($l as $i=>$line)
		$csv .= implode(';', $line)."\r\n";
	
	// Save file
	$yearmonth = date('Ym');
	$date = date('YmdHis');
	$path_netreviews = $path_documents.'/netreviews';
	if (!file_exists($path_netreviews))
		mkdir($path_netreviews);
	if (!file_exists($path_netreviews.'/'.$yearmonth))
		mkdir($path_netreviews.'/'.$yearmonth);
	$filename = $path_netreviews.'/'.$yearmonth.'/'.$date.'.csv';
	file_put_contents($filename, $csv);
	
	$prod = $conf->global->MMIAVISVERIFIES_FTP_PROD_TEST;
	if ($prod && !$conf->global->MMIAVISVERIFIES_FTP_PROD_HOSTNAME) {
		echo '<p>Identifiants FTP Prod non renseignés</p>';
		return false;
	}
	elseif (!$prod && !$conf->global->MMIAVISVERIFIES_FTP_TEST_HOSTNAME) {
		echo '<p>Identifiants FTP Test non renseignés</p>';
		return false;
	}
	
	// FTP
	$ftp_server = $prod ?$conf->global->MMIAVISVERIFIES_FTP_PROD_HOSTNAME :$conf->global->MMIAVISVERIFIES_FTP_TEST_HOSTNAME;
	$ftp_username = $prod ?$conf->global->MMIAVISVERIFIES_FTP_PROD_USERNAME :$conf->global->MMIAVISVERIFIES_FTP_TEST_USERNAME;
	$ftp_password = $prod ?$conf->global->MMIAVISVERIFIES_FTP_PROD_PASSWORD :$conf->global->MMIAVISVERIFIES_FTP_TEST_PASSWORD;
	$ftp_filename = 'orders/'.$date.'.csv';
	
	if (!($ftp = ftp_connect($ftp_server))) {
		echo '<p>Erreur connexion FTP</p>';
		return false;
	}
	var_dump($ftp);
	if (!($login_result = ftp_login($ftp, $ftp_username, $ftp_password))) {
		echo '<p>Erreur Login FTP</p>';
		return false;
	}
	var_dump($login_result);
	ftp_pasv($ftp, true); 
	
	//$buff = ftp_rawlist($ftp, '.');
	//var_dump($buff);
	//$buff = ftp_rawlist($ftp, 'orders');
	//var_dump($buff);

	if (!($upload = ftp_put($ftp, $ftp_filename, $filename, FTP_BINARY))) {
		echo '<p>Erreur Upload FTP</p>';
		return false;
	}
	ftp_close($ftp);
	
	// Mettre à jour
	if ($prod && $upload) {
		// @todo update BDD
		$sql = 'SELECT o2.fk_object
			FROM '.MAIN_DB_PREFIX.'commande_extrafields o2
			WHERE o2.fk_object IN ('.implode(', ', $oids).')';
		echo '<p>'.$sql.'</p>';
        $q = $db->query($sql);
		$oids2 = [];
        while ($p=$q->fetch_object()) {
			$oids2[] = $p->fk_object;
		}

		// update BDD
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_extrafields o2
			SET o2.netreviews_sent = NOW()
			WHERE o2.fk_object IN ('.implode(', ', $oids2).')';
		echo '<p>'.$sql.'</p>';
        $db->query($sql);

		// insert BDD
		$oids3 = array_diff($oids, $oids2);  
		foreach($oids3 as $oid) {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commande_extrafields
				(fk_object, netreviews_sent)
				VALUES
				('.$oid.', NOW())';
			echo '<p>'.$sql.'</p>';
			$db->query($sql);
		}
	}
	var_dump($oids);
	
	//die('ftp ok');
	
	echo '<hr />';
	echo $csv;
}

}