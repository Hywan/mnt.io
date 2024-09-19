use std::task::Poll;

use eyeball_im::{ObservableVector, VectorDiff};
use futures::stream::StreamExt;
use macro_rules_attribute::apply;
use smol::Executor;
use smol_macros::main;

macro_rules! assert_next_eq {
    ( $stream:ident, $expr:expr $(,)? ) => {
        assert_eq!(dbg!($stream.next().await), Some($expr),);
    };
}

#[apply(main!)]
async fn main(_executor: &Executor) {
    let mut observable = ObservableVector::with_capacity(32);
    let mut subscriber = observable.subscribe().into_stream();

    // Push ALL THE VALUES!
    observable.push_back('a');
    observable.push_back('b');
    observable.push_back('c');
    observable.push_back('d');
    observable.push_back('e');
    observable.push_back('f');
    observable.push_back('g');
    observable.push_back('h');
    observable.push_back('i');
    observable.push_back('j');
    observable.push_back('k');
    observable.push_back('l');
    observable.push_back('m');
    observable.push_back('n');
    observable.push_back('o');
    observable.push_back('p');
    observable.push_back('q');

    assert_next_eq!(subscriber, VectorDiff::PushBack { value: 'a' });
}
