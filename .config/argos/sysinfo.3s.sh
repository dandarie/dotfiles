#!/usr/bin/env bash

#based on: https://github.com/kotelnik/plasma-applet-thermal-monitor: ui/config/ConfigTemperatures.qml

#Do this first if you use archlinux-like distros:
#   sudo pacman -S lm_sensors
#   sudo sensors-detect

bitbar=""
temp=$(sensors | grep -oP 'Package.*?\+\K[0-9.]+')
fan_speed=$(sensors | grep -oP 'cpu_fan.*?\K[0-9.]+')
if [ -d "/proc/driver/nvidia" ]; then
    gpu_temp=$(nvidia-smi --query-gpu=temperature.gpu --format=csv,noheader)
    echo " 💻${temp%%.*}° 🎮${gpu_temp%%.*}° ⮾${fan_speed} "
else
    echo "${temp%%.*}° ${fan_speed}"
fi

# https://stackoverflow.com/a/32029995/6074780
echo "---"
cpu_usage=$(grep 'cpu ' /proc/stat | awk '{printf "%0.1f", ($2+$4)*100/($2+$4+$5)}')

echo "Fan speed: 					$fan_speed rpm"
echo "CPU usage: 					${cpu_usage}%"
echo "CPU temperature:			${temp%%.*}°"
echo "GPU temperature:			${gpu_temp%%.*}°"

# memfree=`cat /proc/meminfo | grep MemFree | awk '{print $2}'`;
memava=`cat /proc/meminfo | grep MemAvailable | awk '{print $2}'`;
memtotal=`cat /proc/meminfo | grep MemTotal | awk '{print $2}'`;
printf "Memory usage:				%s%s" `bc <<< "scale=1; ($memtotal-$memava)*100/$memtotal"` %

