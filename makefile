start: vagrant_exists
	vagrant up opnsense

vagrant_exists:
	@command -v vagrant >/dev/null 2>&1 || { echo >&2 "Please install vagrant https://www.vagrantup.com/downloads.html"; exit 1; }

stop:
	vagrant stop

clean:
	vagrant destroy -f

sync_plugin:
	vagrant rsync
	vagrant ssh -c "cd /root/plugins/net/unbound && make upgrade"

fetch_dist:
	vagrant scp opnsense:/root/plugins/net/unbound/work/pkg/'*.txz' ./dist/

install_dependencies:
	brew install xmlstarlet
	chef exec vagrant plugin install vagrant-scp
