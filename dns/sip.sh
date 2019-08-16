#!/usr/bin/env bash

PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

Get_ip(){
	ip=$(wget -qO- -t1 -T2 ipinfo.io/ip)
	if [[ -z "${ip}" ]]; then
		ip=$(wget -qO- -t1 -T2 api.ip.sb/ip)
		if [[ -z "${ip}" ]]; then
			ip=$(wget -qO- -t1 -T2 members.3322.org/dyndns/getip)
			if [[ -z "${ip}" ]]; then
				ip="VPS_IP"
			fi
		fi
	fi
}

Get_ip
if [[ -z "$ip" ]]; then
	echo -e "${Error} 检测外网IP失败 !"
	read -e -p "请手动输入你的服务器外网IP:" ip
	[[ -z "${ip}" ]] && echo "取消..." && over
fi
sip=$ip
# echo $sip

