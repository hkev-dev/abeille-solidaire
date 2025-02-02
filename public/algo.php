<?php

define('MEMBERS_FILE', 'members.json');
define('ENTRY_FEE', 25.0);
define('PAYMENT_SHARE', 0.5); // 50%

class Member {
    public $id;
    public $name;
    public $level = 1;
    public $affiliates = [];
    public $parent = null;
    public $earnings = 0;

    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public function addAffiliate(Member $affiliate) {
        if (count($this->affiliates) < 4) {
            $this->affiliates[] = $affiliate->id;
            $affiliate->parent = $this->id;
            return true;
        }
        return false;
    }

    public function canLevelUp($members) {
        if (count($this->affiliates) < 4) {
            return false;
        }
        foreach ($this->affiliates as $affiliateId) {
            if ($members[$affiliateId]->level < $this->level) {
                return false;
            }
        }
        return true;
    }

    public function levelUp() {
        $this->level++;
    }

    public function addEarnings($amount) {
        $this->earnings += $amount;
    }
}

class AffiliationSystem {
    public $members = [];

    public function __construct() {
        $this->loadMembers();
    }

    public function loadMembers() {
        if (file_exists(MEMBERS_FILE)) {
            $data = json_decode(file_get_contents(MEMBERS_FILE), true);
            foreach ($data as $item) {
                $member = new Member($item['id'], $item['name']);
                $member->level = $item['level'];
                $member->affiliates = $item['affiliates'];
                $member->parent = $item['parent'];
                $member->earnings = $item['earnings'];
                $this->members[$member->id] = $member;
            }
        }
    }

    public function saveMembers() {
        file_put_contents(MEMBERS_FILE, json_encode($this->members, JSON_PRETTY_PRINT));
    }

    public function addMember($name) {
        $id = count($this->members) + 1;
        $newMember = new Member($id, $name);
        $this->members[$id] = $newMember;

        // Assign parent (except for the first member - John Doe)
        if ($id > 1) {
            foreach ($this->members as $member) {
                if ($member->addAffiliate($newMember)) {
                    if (count($member->affiliates) == 4) {
                        // Level up the parent immediately
                        $member->levelUp();
                        $this->checkParentLevelUp($member->parent);
                    }
                    break;
                }
            }
        }

        // Calculate earnings (only if it's not the first member)
        if ($id > 1) {
            $this->calculateEarnings($newMember, ENTRY_FEE);
        }

        $this->saveMembers();
    }

    public function checkParentLevelUp($parentId) {
        if ($parentId !== null && isset($this->members[$parentId])) {
            $parent = $this->members[$parentId];
            if ($parent->canLevelUp($this->members)) {
                $parent->levelUp();
                $this->checkParentLevelUp($parent->parent);
            }
        }
    }

    public function calculateEarnings(Member $member, $amount) {
        if ($member->parent !== null && isset($this->members[$member->parent])) {
            $parent = $this->members[$member->parent];
            $share = $amount * PAYMENT_SHARE;
            $parent->addEarnings($share);
            $this->calculateEarnings($parent, $share); // Recursive call for the parent
        } else {
            // If no parent, the first member gets the remaining amount
            if ($member->id === 1) {
                $member->addEarnings($amount);
            }
        }
    }

    public function displayTable() {
        echo "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse: collapse; width: 100%; text-align: center;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>ID</th>
                <th>Name</th>
                <th>Level</th>
                <th>Parent</th>
                <th>Number of Affiliates</th>
                <th>Affiliate List</th>
                <th>Earnings (€)</th>
              </tr>";

        foreach ($this->members as $member) {
            $parentName = isset($this->members[$member->parent]) ? $this->members[$member->parent]->name : "None";
            $affiliateNames = array_map(fn($id) => $this->members[$id]->name, $member->affiliates);
            echo "<tr>
                    <td>{$member->id}</td>
                    <td>{$member->name}</td>
                    <td>{$member->level}</td>
                    <td>{$parentName}</td>
                    <td>" . count($member->affiliates) . "/4</td>
                    <td>" . implode(", ", $affiliateNames) . "</td>
                    <td>{$member->earnings}</td>
                  </tr>";
        }
        echo "</table>";
    }
}

// Initialize the system
$system = new AffiliationSystem();

// Handle FORM
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['name'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $system->addMember($name);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abeille Solidaire</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        table { margin-top: 20px; }
        input[type="text"], button { padding: 10px; font-size: 16px; }
        button { background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

<h2>Add a New Member</h2>
<form method="post">
    <input type="text" name="name" placeholder="Enter a name" required>
    <button type="submit">Add</button>
</form>

<h2>Member List</h2>
<?php $system->displayTable(); ?>

</body>
</html>