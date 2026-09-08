<?php
// $start_date = date_create("2021-01-01");
// $end_date   = date_create("2021-01-02"); // If you want to include this date, add 1 day

// $interval = DateInterval::createFromDateString('1 day');
// $daterange = new DatePeriod($start_date, $interval ,$end_date);

// foreach($daterange as $date1){
//    echo $date1->format('Y-m-d').'<br>';
// }
 
// echo '<br>';

// // reverse the array

// $daterange = array_reverse(iterator_to_array($daterange));
         
// foreach($daterange as $date1){
//    echo $date1->format('Y-m-d').'<br>';
// }
?>

<?php 
    echo "TEST 3" . "<br>";
    $number_days = array("4");
    $startDate = new DateTime("2022-06-30");
    $endDate = new DateTime("2022-06-30");
    $endDate->modify('+1 day');
    // $endDate->add(new DateInterval('P1D'));

    function isMonday($date) {
        return $date->format('N') === '1';
    }

    function isTuesday($date) {
        return $date->format('N') === '2';
    }

    function isWednesday($date) {
        return $date->format('N') === '3';
    }

    function isThrusday($date) {
        return $date->format('N') === '4';
    }

    function isFriday($date) {
        return $date->format('N') === '5';
    }

    function isSaturday($date) {
        return $date->format('N') === '6';
    }

    function isSunday($date) {
        return $date->format('N') === '7';
    }

    function getDays($start, $end, $number_days) {
        $days = [];

        $datePeriod = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($number_days as $number_day) {
            foreach ($datePeriod as $date) {
                switch ($number_day) {
                    case 1:
                        if (isMonday($date)) $days[] = $date;
                        break;
                    case 2:
                        if (isTuesday($date)) $days[] = $date;
                        break;
                    case 3:
                        if (isWednesday($date)) $days[] = $date;
                        break;
                    case 4:
                        if (isThrusday($date)) $days[] = $date;
                        break;
                    case 5:
                        if (isFriday($date)) $days[] = $date;
                        break;
                    case 6:
                        if (isSaturday($date)) $days[] = $date;
                        break;
                    case 7:
                        if (isSunday($date)) $days[] = $date;
                        break;
                }
            }
        }
        return json_encode($days);
    }

    $data = json_decode(getDays($startDate, $endDate, $number_days));
    $contador = 0;

    foreach ($data as $object) {
        $new_date_format = date('Y-m-d', strtotime($object->date));
        echo "DAY RESULT: " . $new_date_format . "<br>";
    }
?>