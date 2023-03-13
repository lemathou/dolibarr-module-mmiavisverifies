<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Mathieu Moulin <mathieu@iprospective.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmiavisverifies/admin/setup.php
 * \ingroup mmiavisverifies
 * \brief   MMIAvisVerifies setup page.
 */

// Load Dolibarr environment
require_once '../env.inc.php';
require_once '../main_load.inc.php';

$arrayofparameters = array(
	'MMIAVISVERIFIES_FILTER_O_PRESTA'=>array('type'=>'yesno','enabled'=>1),
	'MMIAVISVERIFIES_FILTER_P_PRESTA_ACTIF'=>array('type'=>'yesno','enabled'=>1),
	'MMIAVISVERIFIES_FILTER_C_ARTISAN'=>array('type'=>'yesno','enabled'=>1),
	'MMIAVISVERIFIES_DELAI'=>array('type'=>'date','enabled'=>1),
	'MMIAVISVERIFIES_FTP_PROD_TEST'=>array('type'=>'yesno','enabled'=>1),
	'MMIAVISVERIFIES_FTP_PROD_HOSTNAME'=>array('type'=>'string','enabled'=>1),
	'MMIAVISVERIFIES_FTP_PROD_USERNAME'=>array('type'=>'string','enabled'=>1),
	'MMIAVISVERIFIES_FTP_PROD_PASSWORD'=>array('type'=>'string','enabled'=>1),
	'MMIAVISVERIFIES_FTP_TEST_HOSTNAME'=>array('type'=>'string','enabled'=>1),
	'MMIAVISVERIFIES_FTP_TEST_USERNAME'=>array('type'=>'string','enabled'=>1),
	'MMIAVISVERIFIES_FTP_TEST_PASSWORD'=>array('type'=>'string','enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM1'=>array('type'=>'string', 'css'=>'minwidth500' ,'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
	//'MMIAVISVERIFIES_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
);

require_once('../../mmicommon/admin/mmisetup_1.inc.php');
