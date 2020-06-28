<!DOCTYPE html>
<html lang="en">

<head>
<title>Look up insurance</title>
</head>

<body>

<?php
require_once "htmlhelpers.php";

// TBD: type phone number fmt??
// TBD: date

h3("Look up rates");

?>

<form action="report.php" method="post">

Name: <input type="text" name="name"><br>
Gender: <input type="text" name="gender"><br>
E-mail: <input type="email" name="email"><br>
Phone country code: <input type="text" name="phonecc"><br>
Phone: <input type="text" name="phone"><br>		
Date of Birth: <input type="date" name="dob"  placeholder="YYYY-MM-DD"><br>  
Nationality: <input type="text" name="nationality"><br> 
Country of residence: <input type="text" name="residence"><br>
Home country or country you wish to travel to, or be evacuated to, for medical treatment: <input type="text" name="evac_country"><br>
<br>
<p>For family coverage ("child" is up to 18 years old):</p>
Age of spouse to be covered: <input type="number" name="spouse_age" value="-1"><br>
Age of 1st child to be covered: <input type="number" name="child1_age" value="-1"><br>
Age of 2nd child to be covered: <input type="number" name="child2_age" value="-1"><br>
Age of 3rd child to be covered: <input type="number" name="child3_age" value="-1"><br>
<input type="submit">
</form>

</body>

</html>