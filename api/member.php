<?php
include 'db/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}
header('Content-Type: application/json; charset=utf-8');
$json = file_get_contents('php://input');
$obj = json_decode($json);
$output = array();
date_default_timezone_set('Asia/Calcutta');
$timestamp = date('Y-m-d H:i:s');

// SEARCH BLOCK 
if (isset($obj->search_text)) {
    $search_text = $conn->real_escape_string($obj->search_text);

    $search_terms = explode(' ', $search_text);
    $where_conditions = [];
    
    foreach ($search_terms as $term) {
        if (!empty($term)) {
            $term = trim($term);
            $where_conditions[] = "(`name` LIKE '%$term%' OR `member_no` LIKE '%$term%' OR `father_name` LIKE '%$term%' OR `phone` LIKE '%$term%' OR `section_no` LIKE '%$term%' OR `house` LIKE '%$term%' OR `jamin_name` LIKE '%$term%' )";
  
        }
    }
    
    $sql = "SELECT id,member_id,name,member_no,father_name,section_no,house,jamin_name FROM `members` WHERE `delete_at` = 0";
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    $sql .= " ORDER BY `id` DESC";
    $result = $conn->query($sql);
    $output["head"]["code"] = 200;
    $output["head"]["msg"] = "Success";
    $output["body"]["members"] = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $output["body"]["members"][] = $row;
        }
    } else {
        $output["head"]["msg"] = "No records found";
    }
}
// <<<<<<<<<<===================== This is to Create member =====================>>>>>>>>>>
else if (isset($obj->member_no) && !isset($obj->edit_member_id)) {
    $member_no = $conn->real_escape_string($obj->member_no);
    $name = $conn->real_escape_string($obj->name);
    $father_name = $conn->real_escape_string($obj->father_name ?? '');
    $section_no = $conn->real_escape_string($obj->section_no ?? '');
    $house = $conn->real_escape_string($obj->house ?? '');
    $shop = $conn->real_escape_string($obj->shop ?? '');
    $village = $conn->real_escape_string($obj->village ?? '');
    $pincode = $conn->real_escape_string($obj->pincode ?? '');
    $phone = $conn->real_escape_string($obj->phone ?? '');
    $note = $conn->real_escape_string($obj->note ?? '');
    $jamin_name = $conn->real_escape_string($obj->jamin_name ?? '');
    $jamin_father = $conn->real_escape_string($obj->jamin_father ?? '');
    $jamin_address = $conn->real_escape_string($obj->jamin_address ?? '');
    $jamin_phone = $conn->real_escape_string($obj->jamin_phone ?? '');

    // Check if member number already exists
    $check = $conn->query("SELECT id FROM members WHERE member_no = '$member_no'");
    if ($check->num_rows > 0) {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Member number already exists!";
        $check = $conn->query("SELECT id FROM members WHERE member_no = '$member_no' AND delete_at = 0");
        echo json_encode($output, JSON_NUMERIC_CHECK);
        exit;
    }

    // Create new member
    $createMember = "INSERT INTO `members` (
        `member_no`, `name`, `father_name`, `section_no`, `house`, `shop`, 
        `village`, `pincode`, `phone`, `note`, `jamin_name`, `jamin_father`, 
        `jamin_address`, `jamin_phone`, `created_at`
    ) VALUES (
        '$member_no', '$name', '$father_name', '$section_no', '$house', '$shop', 
        '$village', '$pincode', '$phone', '$note', '$jamin_name', '$jamin_father', 
        '$jamin_address', '$jamin_phone', '$timestamp'
    )";
    
    if ($conn->query($createMember)) {
        $id = $conn->insert_id;
        // GENERATE UNIQUE ID using the new member's ID
        $unique_member_id = uniqueID("MEMBER", $id);
        $updateUniqueId = "UPDATE members SET member_id = '$unique_member_id' WHERE id = '$id'";
        $conn->query($updateUniqueId);
        $output["head"]["code"] = 200;
        $output["head"]["msg"] = "Successfully member Created";
        $output["body"]["member_no"] = $member_no;
        $output["body"]["id"] = $id;
         $output["body"]["member_id"] = $unique_member_id;
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Failed to create member. Please try again.";
    }
}



// <<<<<<<<<<===================== This is to Delete the member =====================>>>>>>>>>>

else if (isset($obj->delete_member_id)) {
    $delete_member_id = $conn->real_escape_string($obj->delete_member_id);
    
    if (!empty($delete_member_id)) {
        $softDeleteMember = "UPDATE `members` SET `delete_at` = 1 WHERE `id`='$delete_member_id'";
        
        if ($conn->query($softDeleteMember)) {
            if ($conn->affected_rows > 0) {
                $output["head"]["code"] = 200;
                $output["head"]["msg"] = "Member Deleted Successfully!";
            } else {
                $output["head"]["code"] = 404;
                $output["head"]["msg"] = "Member not found.";
            }
        } else {
            $output["head"]["code"] = 500;
            $output["head"]["msg"] = "Failed to delete member. Please try again.";
        }
    } else {
        $output["head"]["code"] = 400;
        $output["head"]["msg"] = "Please provide member ID to delete.";
    }
}
echo json_encode($output, JSON_NUMERIC_CHECK);
?>