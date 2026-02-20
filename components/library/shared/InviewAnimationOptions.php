<?php

trait Components_InviewAnimationOptions {
	private function normalize_inview_animation($animation): string {
		$animation = trim((string) $animation);
		if ($animation === '') {
			return '';
		}

		$animation = str_replace('_', '-', $animation);
		return preg_replace('/^animate-/', '', $animation);
	}

	private function normalize_inview_animation_speed($speed): float {
		$value = is_numeric($speed) ? (float) $speed : 1.0;
		return max(0.1, min($value, 10.0));
	}
}
