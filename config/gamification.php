<?php
// config/gamification.php

/**
 * 1. Get Rank and XP based on Quiz Score
 */
function calculateReward($score) {
    if ($score == 100) {
        return ['rank' => 'S (Legendary)', 'xp' => 100, 'badge' => '🌟'];
    } elseif ($score >= 80) {
        return ['rank' => 'A (Master)', 'xp' => 80, 'badge' => '🔥'];
    } elseif ($score >= 60) {
        return ['rank' => 'B (Expert)', 'xp' => 50, 'badge' => '✅'];
    } elseif ($score >= 40) {
        return ['rank' => 'C (Apprentice)', 'xp' => 20, 'badge' => '⚠️'];
    } else {
        return ['rank' => 'D (Novice)', 'xp' => 10, 'badge' => '❌'];
    }
}

/**
 * 2. Calculate XP needed for the NEXT level
 * Formula: Level * 100 (Lvl 1=100xp, Lvl 2=200xp, etc)
 */
function getXPNeeded($current_level) {
    return $current_level * 100;
}

/**
 * 3. Process Level Up
 * Checks if current XP is enough to level up. 
 * Handles multiple level-ups at once (e.g. if you get 500 XP at level 1).
 */
function checkLevelUp($current_level, $current_xp) {
    $xp_needed = getXPNeeded($current_level);
    
    // While we have enough XP to level up...
    while ($current_xp >= $xp_needed) {
        $current_xp -= $xp_needed; // Subtract the cost
        $current_level++;          // Level Up!
        $xp_needed = getXPNeeded($current_level); // Recalculate for next level
    }
    
    return ['level' => $current_level, 'xp' => $current_xp];
}
?>