use eyeball::Observable;

fn main() {
    smol::block_on(async {
        let mut observable = Observable::new(7);

        let mut subscriber = Observable::subscribe(&observable);

        dbg!(Observable::get(&observable));
        dbg!(subscriber.get());

        Observable::set(&mut observable, 13);

        dbg!(Observable::get(&observable));
        dbg!(subscriber.get());

        dbg!(subscriber.next().await);
    })
}
