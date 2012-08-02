<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

class ilModulesTestQuestionPoolSuite extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$suite = new ilModulesTestQuestionPoolSuite();
	
		// Questiontypes
		// -------------------------------------------------------------------------------------------------------------
		require_once("./Modules/TestQuestionPool/test/ilassSingleChoiceTest.php");
		//$suite->addTestSuite("ilassSingleChoiceTest");
		// Incompatible with local mode
		
		require_once("./Modules/TestQuestionPool/test/ilassMultipleChoiceTest.php");
		//$suite->addTestSuite("ilassMultipleChoiceTest");
		// Incompatible with local mode
		
		require_once("./Modules/TestQuestionPool/test/assErrorTextTest.php");
		$suite->addTestSuite("assErrorTextTest");

		// Answertypes
		// -------------------------------------------------------------------------------------------------------------
		require_once("./Modules/TestQuestionPool/test/assAnswerErrorTextTest.php");
		$suite->addTestSuite("assAnswerErrorTextTest");		
		
		
		return $suite;
	}
}
?>
